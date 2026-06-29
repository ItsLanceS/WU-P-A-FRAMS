<?php
/**
 * ApiController
 *
 * PHP relay between the browser and the Python Flask recognition service.
 * The browser sends a base64 image → PHP forwards to Python → PHP returns result.
 */
class ApiController
{
    // ── Recognize endpoint ────────────────────────────────

    public function recognize(): void
    {
        header('Content-Type: application/json');

        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            http_response_code(400);
            echo json_encode(['error' => 'No data received']);
            return;
        }

        $data = json_decode($raw, true);
        if (empty($data['image'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing image field']);
            return;
        }

        $requestedAction = ($data['action'] ?? 'time_in') === 'time_out' ? 'time_out' : 'time_in';
        $eventId         = (int)($data['event_id'] ?? 0);

        // Forward to Python Flask API
        $ch = curl_init(PYTHON_API_URL . '/recognize');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['image' => $data['image']]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => PYTHON_API_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error || !$response) {
            echo json_encode(['error' => 'Face recognition service is unavailable. Please start the Python server.']);
            return;
        }

        $result = json_decode($response, true);

        // If recognized, auto-mark attendance
        if (!empty($result['success']) && !empty($result['result']['student_id'])) {
            $rec        = $result['result'];
            $studentSid = $rec['student_id'];
            $confidence = (float)($rec['confidence'] ?? 0);

            if ($confidence >= RECOGNITION_MIN_CONF) {
                $student = (new Student())->findActiveByStudentId($studentSid);
                if ($student) {
                    $attendance = new Attendance();
                    $today      = date('Y-m-d');
                    $time       = date('H:i:s');
                    $lateCutoff = LATE_TIME;
                    if ($eventId > 0) {
                        $event = (new AttendanceEvent())->findById($eventId);
                        if (!$event || $event['event_date'] !== $today || !(int)$event['is_active']) {
                            $result['attendance'] = ['marked' => 'invalid_event'];
                            $result['student']    = [
                                'name'   => $student['name'],
                                'sid'    => $student['student_id'],
                                'course' => $student['course'],
                                'year'   => $student['year'] ?? '',
                                'section'=> $student['section'] ?? '',
                                'email'  => $student['email'] ?? '',
                                'photo_url' => !empty($student['photo']) ? (UPLOAD_URL . $student['photo']) : null,
                            ];
                            http_response_code($httpCode ?: 200);
                            echo json_encode($result);
                            return;
                        }
                        $lateCutoff = (string)$event['late_time'];
                    }

                    $existing   = $attendance->getByStudentAndDate((int)$student['id'], $today, $eventId ?: null);
                    $markResult = [];

                    if ($requestedAction === 'time_in' && !$existing) {
                        $attendance->markTimeIn((int)$student['id'], $today, $time, $confidence, 'facial_recognition', $eventId ?: null, $lateCutoff);
                        $markResult = ['marked' => 'time_in', 'time' => $time];
                    } elseif ($requestedAction === 'time_in' && $existing) {
                        $markResult = ['marked' => 'already_timed_in'];
                    } elseif ($requestedAction === 'time_out' && !$existing) {
                        $markResult = ['marked' => 'missing_time_in'];
                    } elseif ($requestedAction === 'time_out' && !$existing['time_out']) {
                        $attendance->markTimeOut((int)$existing['id'], $time);
                        $markResult = ['marked' => 'time_out', 'time' => $time];
                    } else {
                        $markResult = ['marked' => 'already_complete'];
                    }

                    $result['attendance'] = $markResult;
                    $result['student']    = [
                        'name'   => $student['name'],
                        'sid'    => $student['student_id'],
                        'course' => $student['course'],
                        'year'   => $student['year'] ?? '',
                        'section'=> $student['section'] ?? '',
                        'email'  => $student['email'] ?? '',
                        'photo_url' => !empty($student['photo']) ? (UPLOAD_URL . $student['photo']) : null,
                    ];
                } else {
                    $result['success'] = false;
                    $result['message'] = 'Matched profile is inactive or missing';
                    if (isset($result['result']) && is_array($result['result'])) {
                        $result['result']['student_id'] = null;
                    }
                }
            }
        }

        http_response_code($httpCode ?: 200);
        echo json_encode($result);
    }

    // ── Python health check ───────────────────────────────

    public function health(): void
    {
        header('Content-Type: application/json');
        $ch = curl_init(PYTHON_API_URL . '/health');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response && $httpCode === 200) {
            echo json_encode(['status' => 'ok', 'python' => json_decode($response, true)]);
        } else {
            echo json_encode(['status' => 'offline', 'message' => 'Python service is not running.']);
        }
    }
}
