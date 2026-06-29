(function (global) {
  function $(value) {
    return typeof value === 'string' ? document.getElementById(value) : value;
  }

  function updateHiddenInput(inputEl, images) {
    if (inputEl) {
      inputEl.value = JSON.stringify(images);
    }
  }

  function playSuccessTone() {
    if (!global.AudioContext && !global.webkitAudioContext) {
      return;
    }

    const AudioContextCtor = global.AudioContext || global.webkitAudioContext;
    const context = new AudioContextCtor();
    const oscillator = context.createOscillator();
    const gainNode = context.createGain();

    oscillator.type = 'sine';
    oscillator.frequency.value = 880;
    gainNode.gain.value = 0.03;

    oscillator.connect(gainNode);
    gainNode.connect(context.destination);

    oscillator.start();
    gainNode.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + 0.2);
    oscillator.stop(context.currentTime + 0.2);
  }

  function stopStream(videoEl) {
    const stream = videoEl?.srcObject;
    if (stream && typeof stream.getTracks === 'function') {
      stream.getTracks().forEach(function (track) {
        track.stop();
      });
    }
    if (videoEl) {
      videoEl.srcObject = null;
    }
  }

  async function startStream(videoEl, facingMode) {
    if (!navigator.mediaDevices?.getUserMedia) {
      throw new Error('Camera access is not supported in this browser.');
    }

    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: facingMode || 'user' },
      audio: false,
    });

    videoEl.srcObject = stream;
    await videoEl.play();
    return stream;
  }

  function captureFrame(videoEl, width, quality) {
    const canvas = document.createElement('canvas');
    const ratio = videoEl.videoWidth && videoEl.videoHeight ? (videoEl.videoHeight / videoEl.videoWidth) : 0.75;
    canvas.width = width || 640;
    canvas.height = Math.round(canvas.width * ratio);
    canvas.getContext('2d').drawImage(videoEl, 0, 0, canvas.width, canvas.height);
    return canvas.toDataURL('image/jpeg', quality || 0.85);
  }

  function getFaceCountText(count) {
    if (!count) {
      return 'No face';
    }

    return count + ' face' + (count > 1 ? 's' : '');
  }

  function drawRoundedRectPath(context, x, y, width, height, radius) {
    if (!context) {
      return;
    }

    if (typeof context.roundRect === 'function') {
      context.roundRect(x, y, width, height, radius);
      return;
    }

    context.rect(x, y, width, height);
  }

  function initEnrollWebcam(videoId, toggleId, stopId, captureId, previewId, inputId) {
    const videoEl = $(videoId);
    const toggleBtn = $(toggleId);
    const stopBtn = $(stopId);
    const captureBtn = $(captureId);
    const previewEl = $(previewId);
    const inputEl = $(inputId);
    const sectionEl = document.getElementById('webcamSection');
    const images = [];

    if (!videoEl || !toggleBtn || !stopBtn || !captureBtn || !previewEl || !inputEl || !sectionEl) {
      return;
    }

    function removeImageAt(index) {
      images.splice(index, 1);
      updateHiddenInput(inputEl, images);
      renderPreviews();
    }

    previewEl.addEventListener('click', function (event) {
      const removeBtn = event.target.closest('button[data-remove-index]');
      if (!removeBtn) {
        return;
      }

      removeImageAt(Number(removeBtn.dataset.removeIndex));
    });

    function renderPreviews() {
      previewEl.innerHTML = '';
      images.forEach(function (image, index) {
        const wrapper = document.createElement('div');
        wrapper.className = 'capture-chip';

        const img = document.createElement('img');
        img.src = image;
        img.alt = 'Captured face ' + (index + 1);
        wrapper.appendChild(img);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.dataset.removeIndex = String(index);
        removeBtn.className = 'btn btn-sm btn-danger rounded-circle p-1 lh-1';
        removeBtn.innerHTML = '<i class="bi bi-x"></i>';
        wrapper.appendChild(removeBtn);

        previewEl.appendChild(wrapper);
      });

      updateHiddenInput(inputEl, images);
    }

    async function openCamera() {
      sectionEl.classList.remove('d-none');
      toggleBtn.disabled = true;
      try {
        await startStream(videoEl, 'user');
      } catch (error) {
        sectionEl.classList.add('d-none');
        alert(error.message || 'Unable to access the webcam.');
      } finally {
        toggleBtn.disabled = false;
      }
    }

    toggleBtn.addEventListener('click', openCamera);

    stopBtn.addEventListener('click', function () {
      stopStream(videoEl);
      sectionEl.classList.add('d-none');
    });

    captureBtn.addEventListener('click', function () {
      if (!videoEl.srcObject) {
        return;
      }

      if (images.length >= 10) {
        alert('You have reached the maximum number of webcam captures.');
        return;
      }

      images.push(captureFrame(videoEl, 480, 0.9));
      renderPreviews();
    });

    global.addEventListener('beforeunload', function () {
      stopStream(videoEl);
    });
  }

  function initAttendanceWebcam(options) {
    const videoEl = $(options.videoEl);
    const overlayCanvas = $(options.overlayCanvas);
    const startBtn = $(options.startBtn);
    const stopBtn = $(options.stopBtn);
    const scanOverlay = $(options.scanOverlay);
    const resultEl = $(options.resultEl);
    const faceCountEl = $(options.faceCountEl);
    const lastCaptureEl = $(options.lastCaptureEl);
    const intervalSelect = $(options.intervalSelect);
    const modeToggle = $(options.modeToggle);
    const eventSelect = $(options.eventSelect);
    const logContainer = $(options.logContainer);
    const modalDelaySelect = $(options.modalDelaySelect);
    const modeLabel = document.querySelector('label[for="' + options.modeToggle + '"]');
    const attendanceModalEl = $(options.attendanceModalId || 'attendanceRecordModal');
    const overlayContext = overlayCanvas ? overlayCanvas.getContext('2d') : null;
    let timerId = null;
    let modalHideTimerId = null;
    let isAttendanceModalOpen = false;
    let modalReopenAllowedAt = 0;
    let lastTrackedFaces = [];
    let lastTrackedLabel = '';
    let lastTrackedLabelExpiresAt = 0;
    let busy = false;

    if (!videoEl || !startBtn || !stopBtn || !resultEl || !intervalSelect || !modeToggle) {
      return;
    }

    function getAction() {
      return modeToggle.checked ? 'time_in' : 'time_out';
    }

    function updateModeLabel() {
      if (modeLabel) {
        modeLabel.textContent = modeToggle.checked ? 'Time-In Mode' : 'Time-Out Mode';
      }
    }

    function syncOverlayCanvasSize() {
      if (!overlayCanvas || !videoEl) {
        return;
      }

      const width = videoEl.clientWidth || videoEl.videoWidth;
      const height = videoEl.clientHeight || videoEl.videoHeight;

      if (!width || !height) {
        return;
      }

      if (overlayCanvas.width !== width || overlayCanvas.height !== height) {
        overlayCanvas.width = width;
        overlayCanvas.height = height;
      }
    }

    function clearOverlay() {
      if (!overlayContext || !overlayCanvas) {
        return;
      }

      overlayContext.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
    }

    function drawFaceLabel(label, x, y, width) {
      if (!overlayContext) {
        return;
      }

      overlayContext.font = '600 15px Segoe UI, sans-serif';
      const textWidth = overlayContext.measureText(label).width;
      const pillWidth = Math.max(textWidth + 20, Math.min(width, 90));
      const pillX = Math.max(8, x + (width - pillWidth) / 2);
      const pillY = Math.max(10, y - 34);

      overlayContext.fillStyle = 'rgba(14, 38, 26, 0.92)';
      overlayContext.beginPath();
      drawRoundedRectPath(overlayContext, pillX, pillY, pillWidth, 28, 12);
      overlayContext.fill();

      overlayContext.fillStyle = '#ffffff';
      overlayContext.textAlign = 'center';
      overlayContext.textBaseline = 'middle';
      overlayContext.fillText(label, pillX + pillWidth / 2, pillY + 14);
    }

    function drawTrackedFaces() {
      clearOverlay();

      if (!overlayContext || !overlayCanvas || !lastTrackedFaces.length) {
        return;
      }

      const scaleX = overlayCanvas.width / (videoEl.videoWidth || overlayCanvas.width);
      const scaleY = overlayCanvas.height / (videoEl.videoHeight || overlayCanvas.height);
      const activeLabel = Date.now() < lastTrackedLabelExpiresAt ? lastTrackedLabel : '';

      lastTrackedFaces.forEach(function (face, index) {
        const box = face.boundingBox || face;
        const x = box.x * scaleX;
        const y = box.y * scaleY;
        const width = box.width * scaleX;
        const height = box.height * scaleY;

        overlayContext.strokeStyle = 'rgba(45, 196, 109, 0.96)';
        overlayContext.lineWidth = 3;
        overlayContext.beginPath();
        drawRoundedRectPath(overlayContext, x, y, width, height, 18);
        overlayContext.stroke();

        overlayContext.fillStyle = 'rgba(45, 196, 109, 0.18)';
        overlayContext.fill();

        const label = index === 0 && activeLabel ? activeLabel : 'Face detected';
        drawFaceLabel(label, x, y, width);
      });
    }

    function startFaceTracking() {
      syncOverlayCanvasSize();
      drawTrackedFaces();
    }

    function stopFaceTracking() {
      lastTrackedFaces = [];
      lastTrackedLabel = '';
      lastTrackedLabelExpiresAt = 0;
      clearOverlay();
    }

    function setResultState(type, title, message, detailHtml) {
      let iconHtml = '<i class="bi bi-person-check-fill text-primary"></i>';
      if (type === 'error') {
        iconHtml = '<i class="bi bi-x-octagon-fill text-danger"></i>';
      } else if (type === 'warning') {
        iconHtml = '<i class="bi bi-exclamation-triangle-fill text-warning"></i>';
      }

      resultEl.innerHTML = '';
      const wrapper = document.createElement('div');
      wrapper.className = 'recognition-result-card ' + type + ' p-3';
      wrapper.innerHTML = [
        '<div class="d-flex align-items-start gap-3">',
        '<div class="fs-2">' + iconHtml + '</div>',
        '<div class="flex-grow-1">',
        '<div class="fw-bold mb-1">' + title + '</div>',
        '<div class="text-secondary small">' + message + '</div>',
        detailHtml || '',
        '</div>',
        '</div>'
      ].join('');
      resultEl.appendChild(wrapper);
    }

    function getAttendanceModalInstance() {
      if (!attendanceModalEl) {
        return null;
      }

      if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        return null;
      }

      return bootstrap.Modal.getOrCreateInstance(attendanceModalEl);
    }

    if (attendanceModalEl && attendanceModalEl.parentElement !== document.body) {
      document.body.appendChild(attendanceModalEl);
    }

    if (attendanceModalEl) {
      attendanceModalEl.addEventListener('shown.bs.modal', function () {
        isAttendanceModalOpen = true;
        global.clearTimeout(timerId);
      });

      attendanceModalEl.addEventListener('hidden.bs.modal', function () {
        isAttendanceModalOpen = false;
        modalReopenAllowedAt = Date.now() + Number(options.modalReopenCooldownMs || 4000);
        if (videoEl.srcObject) {
          scheduleScan();
        }
      });
    }

    function getModalAutoCloseMs() {
      if (modalDelaySelect) {
        return Number(modalDelaySelect.value || 0);
      }

      return Number(options.modalAutoCloseMs || 3000);
    }

    function showAttendanceModal(data) {
      const attendanceModalInstance = getAttendanceModalInstance();

      if (!attendanceModalEl || !attendanceModalInstance || !data?.student || !data?.attendance) {
        return;
      }

      if (isAttendanceModalOpen) {
        return;
      }

      if (Date.now() < modalReopenAllowedAt) {
        return;
      }

      const student = data.student;
      const attendance = data.attendance;
      const recordedStatus = attendance.marked === 'time_out' ? 'Time-Out' : 'Time-In';
      const yearSection = [student.year || '', student.section || ''].filter(Boolean).join(' / ');

      const photoEl = document.getElementById('recordStudentPhoto');
      const nameEl = document.getElementById('recordStudentName');
      const sidEl = document.getElementById('recordStudentSid');
      const courseEl = document.getElementById('recordStudentCourse');
      const yearSectionEl = document.getElementById('recordStudentYearSection');
      const statusEl = document.getElementById('recordAttendanceStatus');
      const timeEl = document.getElementById('recordAttendanceTime');

      if (photoEl) {  
        if (student.photo_url) {
          photoEl.src = student.photo_url;
          photoEl.classList.remove('attendance-record-photo-placeholder');
        } else {
          photoEl.src = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22120%22 height=%22120%22 viewBox=%220 0 120 120%22%3E%3Crect width=%22120%22 height=%22120%22 rx=%2216%22 fill=%22%23e8efe9%22/%3E%3Ccircle cx=%2260%22 cy=%2245%22 r=%2220%22 fill=%22%23a2b8a9%22/%3E%3Cpath d=%22M25 102c0-17 16-30 35-30s35 13 35 30%22 fill=%22%23a2b8a9%22/%3E%3C/svg%3E';
          photoEl.classList.add('attendance-record-photo-placeholder');
        }
      }

      if (nameEl) {
        nameEl.textContent = student.name || 'Unknown Student';
      }
      if (sidEl) {
        sidEl.textContent = student.sid || '';
      }
      if (courseEl) {
        courseEl.textContent = student.course || 'N/A';
      }
      if (yearSectionEl) {
        yearSectionEl.textContent = yearSection || 'N/A';
      }
      if (statusEl) {
        statusEl.textContent = recordedStatus;
      }
      if (timeEl) {
        timeEl.textContent = attendance.time || new Date().toLocaleTimeString();
      }

      attendanceModalInstance.show();
      global.clearTimeout(timerId);

      global.clearTimeout(modalHideTimerId);
      const modalAutoCloseMs = getModalAutoCloseMs();
      if (modalAutoCloseMs > 0) {
        modalHideTimerId = global.setTimeout(function () {
          attendanceModalInstance.hide();
        }, modalAutoCloseMs);
      }
    }

    function renderRecognition(data) {
      const result = data.result || {};
      const hasFaceBox = Boolean(result.face_box);
      lastTrackedFaces = hasFaceBox ? [result.face_box] : [];
      syncOverlayCanvasSize();

      if (data.error) {
        clearOverlay();
        setResultState('error', 'Recognition failed', data.error);
        return;
      }

      if (!data.success || !data.student) {
        lastTrackedLabel = '';
        lastTrackedLabelExpiresAt = 0;
        if (faceCountEl) {
          faceCountEl.textContent = getFaceCountText(Number(result.face_count || lastTrackedFaces.length));
        }
        drawTrackedFaces();
        setResultState('warning', 'No match', 'No student was confidently recognized in the latest frame.');
        return;
      }

      const attendance = data.attendance || {};
      const statusTextMap = {
        time_in: 'Time-in recorded successfully.',
        time_out: 'Time-out recorded successfully.',
        already_timed_in: 'This student already has a time-in record for today.',
        missing_time_in: 'No time-in record exists for this student today.',
        already_complete: 'This student already has both time-in and time-out for today.',
        invalid_event: 'The selected event is invalid or inactive for today.',
      };
      const cardType = (attendance.marked === 'time_in' || attendance.marked === 'time_out') ? 'success' : 'warning';
      const confidence = data.result?.confidence ? Number(data.result.confidence).toFixed(1) + '%' : 'N/A';
      const detailHtml = [
        '<div class="mt-3 small">',
        '<div><span class="text-secondary">Student ID:</span> ' + data.student.sid + '</div>',
        '<div><span class="text-secondary">Course:</span> ' + (data.student.course || 'N/A') + '</div>',
        '<div><span class="text-secondary">Confidence:</span> ' + confidence + '</div>',
        attendance.time ? '<div><span class="text-secondary">Logged at:</span> ' + attendance.time + '</div>' : '',
        '</div>'
      ].join('');

      setResultState(cardType, data.student.name, statusTextMap[attendance.marked] || 'Recognition completed.', detailHtml);

      lastTrackedLabel = data.student.name || '';
      lastTrackedLabelExpiresAt = Date.now() + 4000;
      if (faceCountEl) {
        faceCountEl.textContent = getFaceCountText(Number(result.face_count || lastTrackedFaces.length));
      }
      try {
        drawTrackedFaces();
      } catch (error) {
        if (global.console && typeof global.console.warn === 'function') {
          global.console.warn('Face overlay render skipped:', error);
        }
      }

      if (cardType === 'success') {
        playSuccessTone();
        try {
          showAttendanceModal(data);
        } catch (error) {
          if (global.console && typeof global.console.warn === 'function') {
            global.console.warn('Attendance modal skipped:', error);
          }
        }
      }
    }

    async function refreshLog() {
      if (!logContainer) {
        return;
      }

      const baseLogUrl = options.logUrl || (global.location.pathname + '?page=attendance&action=log_partial');
      const url = new URL(baseLogUrl, global.location.origin);
      if (eventSelect) {
        url.searchParams.set('event_id', String(Number(eventSelect.value || 0)));
      }

      const response = await fetch(url.toString());
      logContainer.innerHTML = await response.text();
    }

    async function scanFrame() {
      if (!videoEl.srcObject || busy || isAttendanceModalOpen) {
        return;
      }

      busy = true;
      faceCountEl.textContent = 'Scanning';
      if (scanOverlay) {
        scanOverlay.classList.remove('d-none');
      }

      try {
        const payload = {
          image: captureFrame(videoEl, 640, 0.8),
          action: getAction(),
          event_id: eventSelect ? Number(eventSelect.value || 0) : 0,
        };

        const response = await fetch(options.apiUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        const data = await response.json();
        const result = data.result || {};
        renderRecognition(data);

        if (data.success && data.attendance && (data.attendance.marked === 'time_in' || data.attendance.marked === 'time_out')) {
          await refreshLog();
        }

        if (lastCaptureEl) {
          lastCaptureEl.textContent = 'Last scan: ' + new Date().toLocaleTimeString();
        }
        if (faceCountEl && !result.face_count) {
          faceCountEl.textContent = data.success ? 'Recognized' : 'No match';
        }
      } catch (error) {
        stopFaceTracking();
        setResultState('error', 'Camera scan failed', error.message || 'Unable to reach the recognition service.');
        faceCountEl.textContent = 'Offline';
      } finally {
        busy = false;
        if (scanOverlay) {
          scanOverlay.classList.add('d-none');
        }
      }
    }

    function scheduleScan() {
      global.clearTimeout(timerId);
      timerId = global.setTimeout(async function runScan() {
        await scanFrame();
        scheduleScan();
      }, Number(intervalSelect.value || 3000));
    }

    async function startScanning() {
      startBtn.disabled = true;
      try {
        await startStream(videoEl, 'user');
        startBtn.classList.add('d-none');
        stopBtn.classList.remove('d-none');
        setResultState('warning', 'Camera ready', 'Scanning will begin automatically using the selected interval.');
        startFaceTracking();
        scheduleScan();
      } catch (error) {
        setResultState('error', 'Unable to start camera', error.message || 'Camera access was denied.');
      } finally {
        startBtn.disabled = false;
      }
    }

    function stopScanning() {
      global.clearTimeout(timerId);
      stopStream(videoEl);
      stopFaceTracking();
      stopBtn.classList.add('d-none');
      startBtn.classList.remove('d-none');
      faceCountEl.textContent = 'Stopped';
      if (scanOverlay) {
        scanOverlay.classList.add('d-none');
      }
      setResultState('warning', 'Camera stopped', 'Start the camera again to resume live recognition.');
    }

    startBtn.addEventListener('click', startScanning);
    stopBtn.addEventListener('click', stopScanning);
    intervalSelect.addEventListener('change', function () {
      if (videoEl.srcObject) {
        scheduleScan();
      }
    });
    modeToggle.addEventListener('change', updateModeLabel);
    global.addEventListener('resize', syncOverlayCanvasSize);

    updateModeLabel();

    global.addEventListener('beforeunload', function () {
      global.clearTimeout(timerId);
      stopFaceTracking();
      stopStream(videoEl);
    });
  }

  global.initEnrollWebcam = initEnrollWebcam;
  global.initAttendanceWebcam = initAttendanceWebcam;
})(globalThis);