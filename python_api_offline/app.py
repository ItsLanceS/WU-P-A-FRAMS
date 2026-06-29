import base64
import io
import json
import os
from datetime import datetime, timezone
from typing import Any

import numpy as np
from flask import Flask, jsonify, request
from PIL import Image, ImageOps

try:
    import cv2

    CV2_AVAILABLE = True
except Exception:
    cv2 = None
    CV2_AVAILABLE = False

app = Flask(__name__)

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DATA_DIR = os.path.join(BASE_DIR, "data")
ENROLLMENTS_FILE = os.path.join(DATA_DIR, "enrollments.json")
if CV2_AVAILABLE:
    CASCADE_PATH = os.path.join(cv2.data.haarcascades, "haarcascade_frontalface_default.xml")
    FACE_CASCADE = cv2.CascadeClassifier(CASCADE_PATH)
else:
    CASCADE_PATH = ""
    FACE_CASCADE = None

# Legacy hash matcher thresholds (balanced for backward compatibility).
MIN_HASH_CONFIDENCE = 68.0
MIN_HASH_DISTANCE_GAP = 3
MAX_HASH_ACCEPT_DISTANCE = 16

# Descriptor matcher thresholds (primary path, closer to JS-style embedding matching).
MAX_DESCRIPTOR_DISTANCE = 0.62
MIN_DESCRIPTOR_DISTANCE_GAP = 0.02
MIN_DESCRIPTOR_CONFIDENCE = 20.0


def ensure_storage() -> None:
    os.makedirs(DATA_DIR, exist_ok=True)
    if not os.path.exists(ENROLLMENTS_FILE):
        with open(ENROLLMENTS_FILE, "w", encoding="utf-8") as fp:
            json.dump({"students": []}, fp)


def read_store() -> dict[str, Any]:
    ensure_storage()
    with open(ENROLLMENTS_FILE, "r", encoding="utf-8") as fp:
        data = json.load(fp)
    if "students" not in data or not isinstance(data["students"], list):
        data = {"students": []}
    return data


def write_store(data: dict[str, Any]) -> None:
    ensure_storage()
    with open(ENROLLMENTS_FILE, "w", encoding="utf-8") as fp:
        json.dump(data, fp, indent=2)


def parse_data_uri(data_uri: str) -> bytes:
    if "," in data_uri:
        _, payload = data_uri.split(",", 1)
    else:
        payload = data_uri
    return base64.b64decode(payload, validate=True)


def load_image(image_bytes: bytes) -> Image.Image:
    image = Image.open(io.BytesIO(image_bytes)).convert("RGB")
    return ImageOps.exif_transpose(image)


def detect_faces(image: Image.Image) -> list[dict[str, int]]:
    if not CV2_AVAILABLE or FACE_CASCADE is None:
        return []

    rgb_array = np.array(image)
    gray_array = cv2.cvtColor(rgb_array, cv2.COLOR_RGB2GRAY)
    detections = FACE_CASCADE.detectMultiScale(
        gray_array,
        scaleFactor=1.1,
        minNeighbors=5,
        minSize=(72, 72),
    )

    faces: list[dict[str, int]] = []
    for x, y, width, height in detections:
        faces.append(
            {
                "x": int(x),
                "y": int(y),
                "width": int(width),
                "height": int(height),
            }
        )

    faces.sort(key=lambda item: item["width"] * item["height"], reverse=True)
    return faces


def crop_to_face(image: Image.Image, face_box: dict[str, int] | None) -> Image.Image:
    if not face_box:
        return image

    x = max(0, face_box["x"])
    y = max(0, face_box["y"])
    width = max(1, face_box["width"])
    height = max(1, face_box["height"])
    right = min(image.width, x + width)
    bottom = min(image.height, y + height)
    return image.crop((x, y, right, bottom))


def image_hash(image: Image.Image) -> str:
    image = image.convert("L")
    image = image.resize((8, 8), Image.Resampling.LANCZOS)
    pixels = list(image.getdata())
    avg = sum(pixels) / len(pixels)
    bits = "".join("1" if px >= avg else "0" for px in pixels)
    return bits


def extract_face_descriptor(image: Image.Image) -> list[float]:
    try:
        gray_array = np.array(image.convert("L").resize((96, 96), Image.Resampling.BILINEAR), dtype=np.float32)
        if CV2_AVAILABLE:
            clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
            gray_u8 = np.clip(gray_array, 0, 255).astype(np.uint8)
            gray_u8 = clahe.apply(gray_u8)
            gray_u8 = cv2.GaussianBlur(gray_u8, (3, 3), 0)
            gray_array = gray_u8.astype(np.float32)

        gy, gx = np.gradient(gray_array)
        magnitude = np.sqrt((gx * gx) + (gy * gy))
        angle = np.degrees(np.arctan2(gy, gx))
        angle = np.mod(angle, 180.0)
        angle_bins = np.clip(((angle / 180.0) * 8).astype(np.int32), 0, 7)

        cell_size = 12
        features: list[float] = []
        for y in range(0, 96, cell_size):
            for x in range(0, 96, cell_size):
                cell_bins = angle_bins[y : y + cell_size, x : x + cell_size].ravel()
                cell_magnitude = magnitude[y : y + cell_size, x : x + cell_size].ravel()
                hist = np.bincount(cell_bins, weights=cell_magnitude, minlength=8).astype(np.float32)
                features.extend(hist.tolist())

        intensity_hist, _ = np.histogram(gray_array, bins=32, range=(0, 256))
        intensity_hist = intensity_hist.astype(np.float32)
        descriptor = np.array(features + intensity_hist.tolist(), dtype=np.float32)
        norm = float(np.linalg.norm(descriptor))
        if norm > 0:
            descriptor /= norm

        return [round(float(value), 6) for value in descriptor.tolist()]
    except Exception:
        return []


def hamming_distance(a: str, b: str) -> int:
    if len(a) != len(b):
        return 64
    return sum(ch1 != ch2 for ch1, ch2 in zip(a, b))


def descriptor_distance(a: list[float], b: list[float]) -> float:
    if not a or not b or len(a) != len(b):
        return 2.0

    va = np.array(a, dtype=np.float32)
    vb = np.array(b, dtype=np.float32)
    norm_a = float(np.linalg.norm(va))
    norm_b = float(np.linalg.norm(vb))
    if norm_a == 0.0 or norm_b == 0.0:
        return 2.0

    cosine_similarity = float(np.dot(va, vb) / (norm_a * norm_b))
    cosine_similarity = float(np.clip(cosine_similarity, -1.0, 1.0))
    return 1.0 - cosine_similarity


def representative_distance(distances: list[float], top_k: int = 3) -> float:
    if not distances:
        return 64.0
    ranked = sorted(distances)
    subset = ranked[: max(1, min(top_k, len(ranked)))]
    return float(sum(subset)) / float(len(subset))


def hash_confidence_from_distance(distance: float) -> float:
    # Convert 0..64 bit distance into a confidence percentage.
    score = 100.0 - (distance / 64.0) * 100.0
    return max(0.0, min(100.0, score))


def descriptor_confidence_from_distance(distance: float) -> float:
    # Convert descriptor distance into confidence using max accepted distance as the 0% floor.
    score = 100.0 * (1.0 - (distance / MAX_DESCRIPTOR_DISTANCE))
    return max(0.0, min(100.0, score))


@app.get("/health")
def health() -> Any:
    store = read_store()
    return jsonify(
        {
            "status": "ok",
            "mode": "offline-local",
            "face_detector": "opencv-haarcascade" if CV2_AVAILABLE else "unavailable",
            "students_enrolled": len(store.get("students", [])),
            "timestamp": datetime.now(timezone.utc).isoformat(),
        }
    )


@app.post("/enroll")
def enroll() -> Any:
    if not CV2_AVAILABLE:
        return jsonify({"success": False, "error": "Face detector unavailable on this Python runtime"}), 503

    payload = request.get_json(silent=True) or {}
    student_id = str(payload.get("student_id", "")).strip()
    name = str(payload.get("name", "")).strip()
    images = payload.get("images", [])

    if not student_id or not name:
        return jsonify({"success": False, "error": "student_id and name are required"}), 400
    if not isinstance(images, list) or not images:
        return jsonify({"success": False, "error": "images[] is required"}), 400

    hashes: list[str] = []
    descriptors: list[list[float]] = []
    for img in images:
        try:
            image = load_image(parse_data_uri(str(img)))
            faces = detect_faces(image)
            if not faces:
                continue
            face_image = crop_to_face(image, faces[0])
            full_image = image

            hashes.append(image_hash(face_image))
            hashes.append(image_hash(full_image))

            desc = extract_face_descriptor(face_image)
            if desc:
                descriptors.append(desc)

            full_desc = extract_face_descriptor(full_image)
            if full_desc:
                descriptors.append(full_desc)
        except Exception:
            continue

    if not hashes:
        return jsonify({"success": False, "error": "No valid images were provided"}), 400

    store = read_store()
    students = store["students"]
    replaced = False

    for student in students:
        if str(student.get("student_id")) == student_id:
            student["name"] = name
            student["hashes"] = hashes
            student["descriptors"] = descriptors
            student["updated_at"] = datetime.now(timezone.utc).isoformat()
            replaced = True
            break

    if not replaced:
        students.append(
            {
                "student_id": student_id,
                "name": name,
                "hashes": hashes,
                "descriptors": descriptors,
                "updated_at": datetime.now(timezone.utc).isoformat(),
            }
        )

    write_store(store)

    return jsonify(
        {
            "success": True,
            "message": f"Offline enrollment saved for {name} ({student_id})",
            "mode": "offline-local",
            "samples": len(descriptors) if descriptors else len(hashes),
        }
    )


@app.post("/unenroll")
def unenroll() -> Any:
    payload = request.get_json(silent=True) or {}
    student_id = str(payload.get("student_id", "")).strip()

    if not student_id:
        return jsonify({"success": False, "error": "student_id is required"}), 400

    store = read_store()
    students = store.get("students", [])
    before_count = len(students)
    students = [item for item in students if str(item.get("student_id", "")) != student_id]
    removed = before_count - len(students)
    store["students"] = students
    write_store(store)

    return jsonify(
        {
            "success": True,
            "mode": "offline-local",
            "removed": removed,
            "student_id": student_id,
            "message": "Offline enrollment removed" if removed else "No enrollment found",
        }
    )


@app.post("/recognize")
def recognize() -> Any:
    if not CV2_AVAILABLE:
        return jsonify({"success": False, "error": "Face detector unavailable on this Python runtime"}), 503

    payload = request.get_json(silent=True) or {}
    image_data = payload.get("image")

    if not image_data:
        return jsonify({"success": False, "error": "Missing image field"}), 400

    try:
        image = load_image(parse_data_uri(str(image_data)))
        faces = detect_faces(image)
        primary_face = faces[0] if faces else None
        face_image = crop_to_face(image, primary_face) if primary_face else None
        incoming_hash = image_hash(face_image) if face_image else None
        incoming_descriptor = extract_face_descriptor(face_image) if face_image else None
    except Exception:
        return jsonify({"success": False, "error": "Invalid image payload"}), 400

    if not primary_face or not incoming_hash:
        return jsonify(
            {
                "success": False,
                "message": "No face detected",
                "mode": "offline-local",
                "result": {
                    "student_id": None,
                    "confidence": 0,
                    "face_count": len(faces),
                    "face_box": primary_face,
                },
            }
        )

    store = read_store()
    descriptor_distances: list[dict[str, Any]] = []
    hash_distances: list[dict[str, Any]] = []

    incoming_hashes = [incoming_hash]
    incoming_full_hash = image_hash(image)
    if incoming_full_hash and incoming_full_hash != incoming_hash:
        incoming_hashes.append(incoming_full_hash)

    incoming_descriptors = []
    if incoming_descriptor:
        incoming_descriptors.append(incoming_descriptor)
    incoming_full_descriptor = extract_face_descriptor(image)
    if incoming_full_descriptor:
        incoming_descriptors.append(incoming_full_descriptor)

    for student in store.get("students", []):
        descriptor_values = student.get("descriptors", [])
        descriptor_values_for_student: list[float] = []
        if isinstance(descriptor_values, list) and incoming_descriptors:
            for known_descriptor in descriptor_values:
                if isinstance(known_descriptor, list):
                    for candidate_descriptor in incoming_descriptors:
                        descriptor_values_for_student.append(descriptor_distance(candidate_descriptor, known_descriptor))

        if descriptor_values_for_student:
            best_distance_for_student = representative_distance(descriptor_values_for_student)
            descriptor_distances.append(
                {
                    "student_id": str(student.get("student_id", "")),
                    "name": str(student.get("name", "Unknown")),
                    "distance": best_distance_for_student,
                }
            )

        distances_for_student: list[float] = []
        for known_hash in student.get("hashes", []):
            for candidate_hash in incoming_hashes:
                distances_for_student.append(hamming_distance(candidate_hash, str(known_hash)))

        if distances_for_student:
            best_distance_for_student = representative_distance(distances_for_student)
            hash_distances.append(
                {
                    "student_id": str(student.get("student_id", "")),
                    "name": str(student.get("name", "Unknown")),
                    "distance": best_distance_for_student,
                }
            )

    descriptor_distances.sort(key=lambda item: item["distance"])
    hash_distances.sort(key=lambda item: item["distance"])

    best_descriptor = descriptor_distances[0] if descriptor_distances else None
    second_descriptor = descriptor_distances[1] if len(descriptor_distances) > 1 else None
    best_hash = hash_distances[0] if hash_distances else None
    second_hash = hash_distances[1] if len(hash_distances) > 1 else None

    if best_descriptor is None and best_hash is None:
        return jsonify(
            {
                "success": False,
                "message": "No enrolled students yet",
                "mode": "offline-local",
                "result": {
                    "student_id": None,
                    "confidence": 0,
                    "face_count": len(faces),
                    "face_box": primary_face,
                },
            }
        )

    descriptor_confidence = (
        descriptor_confidence_from_distance(best_descriptor["distance"]) if best_descriptor else 0.0
    )
    descriptor_gap = (
        (second_descriptor["distance"] - best_descriptor["distance"]) if best_descriptor and second_descriptor else 1.0
    )
    descriptor_failed = (
        (best_descriptor is None)
        or (descriptor_confidence < MIN_DESCRIPTOR_CONFIDENCE)
        or (descriptor_gap < MIN_DESCRIPTOR_DISTANCE_GAP)
        or (best_descriptor["distance"] > MAX_DESCRIPTOR_DISTANCE)
    )

    hash_confidence = hash_confidence_from_distance(best_hash["distance"]) if best_hash else 0.0
    hash_gap = (second_hash["distance"] - best_hash["distance"]) if best_hash and second_hash else 64.0
    hash_failed = (
        (best_hash is None)
        or (hash_confidence < MIN_HASH_CONFIDENCE)
        or (hash_gap < MIN_HASH_DISTANCE_GAP)
        or (best_hash["distance"] > MAX_HASH_ACCEPT_DISTANCE)
    )

    if not descriptor_failed:
        metric = "descriptor"
        best_match = best_descriptor
        confidence = descriptor_confidence
        distance_gap = descriptor_gap
    elif not hash_failed:
        metric = "hash"
        best_match = best_hash
        confidence = hash_confidence
        distance_gap = hash_gap
    else:
        metric = "descriptor" if best_descriptor else "hash"
        best_match = best_descriptor if best_descriptor else best_hash
        confidence = descriptor_confidence if best_descriptor else hash_confidence
        distance_gap = descriptor_gap if best_descriptor else hash_gap

        return jsonify(
            {
                "success": False,
                "message": "No confident match",
                "mode": "offline-local",
                "result": {
                    "student_id": None,
                    "confidence": round(confidence, 2),
                    "distance_gap": round(distance_gap, 2),
                    "distance": round(best_match["distance"], 2),
                    "matcher": metric,
                    "multiple_faces_detected": len(faces) > 1,
                    "face_count": len(faces),
                    "face_box": primary_face,
                },
            }
        )

    return jsonify(
        {
            "success": True,
            "mode": "offline-local",
            "result": {
                "student_id": best_match["student_id"],
                "confidence": round(confidence, 2),
                "distance_gap": round(distance_gap, 2),
                "distance": round(best_match["distance"], 2),
                "matcher": metric,
                "multiple_faces_detected": len(faces) > 1,
                "face_count": len(faces),
                "face_box": primary_face,
            },
            "message": f"Matched {best_match['name']}",
        }
    )


if __name__ == "__main__":
    ensure_storage()
    app.run(host="127.0.0.1", port=5000, debug=False)
