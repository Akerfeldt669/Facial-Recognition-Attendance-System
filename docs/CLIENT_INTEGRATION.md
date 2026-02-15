# Client Integration Guide

This document explains how to integrate the Desktop (Python) and Mobile (Android) client applications with the Facial Recognition Attendance System.

## Overview

The system uses a **client-server architecture**:
- **Clients**: Desktop (Python) and Mobile (Android) apps on student/operator devices
- **Server**: Flask API for facial recognition + PHP/MySQL for administration

## Flask Server API

### Base URL

```
http://<server-ip>:<port>
# Example: http://192.168.1.100:5000
```

### Authentication

Currently, the system does not require authentication for the `/recognize` endpoint. For production, implement API key or token-based authentication.

### Endpoints

#### POST /recognize

Performs facial recognition on an uploaded image.

**Request**:
```
POST /recognize
Content-Type: multipart/form-data

image: <image-file>  # JPEG, PNG format
```

**Response - Success (Known Face)**:
```json
{
  "identity": "Ahmed Samir",
  "similarity": 0.85,
  "status": "known"
}
```

**Response - Unknown Face**:
```json
{
  "identity": "Unknown",
  "similarity": 0.45,
  "status": "unknown"
}
```

**Response - Error**:
```json
{
  "error": "No image uploaded",
  "status": "error"
}
```

### Expected Behavior

- **High Similarity (0.75+)**: Definitely match
- **Medium Similarity (0.60-0.75)**: Likely match (recommend operator verification)
- **Low Similarity (<0.60)**: Probably not match (reject)

### Image Requirements

- **Format**: JPEG, PNG, or similar
- **Size**: 200x200 to 1200x1200 pixels recommended
- **Quality**: Clear, well-lit face, frontal angle
- **File Size**: < 10MB

## Python Desktop Client Example

```python
import requests
import cv2

# Configuration
FLASK_SERVER = "http://192.168.1.100:5000"
RECOGNIZE_ENDPOINT = f"{FLASK_SERVER}/recognize"

# Capture image from webcam
cap = cv2.VideoCapture(0)
ret, frame = cap.read()

# Send to Flask server
files = {'image': cv2.imencode('.jpg', frame)[1].tobytes()}
response = requests.post(RECOGNIZE_ENDPOINT, files={'image': open('temp.jpg', 'rb')})

# Parse response
result = response.json()
if result['status'] == 'known':
    print(f"Recognized: {result['identity']}")
    print(f"Confidence: {result['similarity']*100:.1f}%")
    # Send to PHP endpoint to record attendance
else:
    print("Face not recognized")
```

## Android Mobile Client Example

```java
// Using OkHttp3 or Retrofit

RequestBody requestBody = new MultipartBody.Builder()
    .setType(MultipartBody.FORM)
    .addFormDataPart("image", "face.jpg", 
        RequestBody.create(MediaType.parse("image/jpeg"), bitmap))
    .build();

Request request = new Request.Builder()
    .url("http://192.168.1.100:5000/recognize")
    .post(requestBody)
    .build();

try (Response response = client.newCall(request).execute()) {
    JSONObject result = new JSONObject(response.body().string());
    String identity = result.getString("identity");
    double similarity = result.getDouble("similarity");
    
    if (similarity > 0.67) {
        // Record attendance via PHP
        recordAttendance(identity);
    }
}
```

## Recording Attendance

After successful recognition, record attendance using the PHP endpoint:

```
POST http://<server-ip>/backend/php/attendance/insert_presence.php

Parameters:
- identity: String (full name from recognition)
- similarity: Float (confidence score)
- status: String ("known" or "unknown")
```

Example PHP attendance endpoint handling:

```php
<?php
$identity = $_POST['identity'] ?? null;
$similarity = $_POST['similarity'] ?? null;

if ($identity && $similarity > 0.60) {
    // Get user ID
    $stmt = $conn->prepare("SELECT id_utilisateur FROM utilisateurs 
                          WHERE CONCAT(nom, ' ', prenom) = ?");
    $stmt->execute([$identity]);
    
    if ($user = $stmt->fetch()) {
        // Record presence
        $stmt = $conn->prepare("INSERT INTO presences 
                              (id_etudiant, id_seance, heure_reconnaissance) 
                              VALUES (?, ?, NOW())");
        $stmt->execute([$user['id_utilisateur'], $current_session_id]);
        
        echo json_encode(["success" => true, "message" => "Attendance recorded"]);
    }
}
```

## Network Configuration

### For Development (Same Network)

```
┌─────────────────────────────┐
│   Local Network (192.168.1.x)│
├──────────┬──────────┬────────┤
│  Client  │ Client   │ Flask  │
│  Desktop │ Android  │ Server │
│          │          │        │
│ :8000   │ :8000   │ :5000  │
└─────────┴──────────┴────────┘
```

**Configuration**:
```
CLIENT_IP = "192.168.1.100"  # Flask server IP
CLIENT_PORT = 5000
```

### For Production (Different Networks)

```
┌──────────────────────────────────────┐
│      Internet / VPN Network          │
├──────────┬──────────┬────────────────┤
│  Client  │ Client   │ Flask Server   │
│ (Remote) │ (Remote) │ (Fixed IP/DNS) │
│          │          │                │
└─────────┴──────────┴────────────────┘
```

**Configuration**:
```
CLIENT_HOSTNAME = "attendance.company.com"  # Or static IP
CLIENT_PORT = 5000
USE_HTTPS = true  # Recommended for production
```

## Security Considerations for Clients

1. **Validate Server Certificate** (HTTPS)
   ```python
   response = requests.post(url, ..., verify=True)  # Verify SSL
   ```

2. **Handle Failures Gracefully**
   ```python
   try:
       response = requests.post(url, timeout=5)
   except requests.ConnectionError:
       print("Server unreachable")
   except requests.Timeout:
       print("Request timed out")
   ```

3. **Implement Retry Logic**
   ```python
   for attempt in range(3):
       try:
           response = requests.post(url)
           break
       except:
           if attempt < 2:
               time.sleep(2)  # Wait before retry
   ```

4. **Offline Mode** (Optional)
   - Cache embeddings locally for offline operation
   - Sync attendance when reconnected

## Performance Tuning for Clients

### Image Optimization

```python
import cv2

# Resize image before sending
frame = cv2.imread('face.jpg')
resized = cv2.resize(frame, (480, 480))  # Optimal size
_, buffer = cv2.imencode('.jpg', resized, [cv2.IMWRITE_JPEG_QUALITY, 85])

files = {'image': ('image.jpg', buffer.tobytes(), 'image/jpeg')}
```

### Compression

```python
# Reduce file size
import cv2
_, encoded = cv2.imencode('.jpg', frame, [cv2.IMWRITE_JPEG_QUALITY, 75])
size_mb = len(encoded) / (1024 * 1024)
print(f"Image size: {size_mb:.2f} MB")
```

### Network Optimization

```python
# Use connection pooling (recommended for high-frequency requests)
from requests.adapters import HTTPAdapter
from requests.packages.urllib3.util.retry import Retry

session = requests.Session()
retry_strategy = Retry(total=3, backoff_factor=1)
adapter = HTTPAdapter(max_retries=retry_strategy, pool_connections=10)
session.mount("http://", adapter)
session.mount("https://", adapter)

response = session.post(RECOGNIZE_ENDPOINT, ...)
```

## Testing Checklist

- [ ] Server running and accessible from client
- [ ] `/recognize` endpoint returns valid responses
- [ ] Attendance recording via PHP endpoint works
- [ ] Handles unknown faces correctly
- [ ] Network delays handled gracefully
- [ ] Error messages user-friendly
- [ ] Offline mode functional (if implemented)

---

Contact your server administrator for server IP, port, and credentials.
