# System Architecture

## Overview

The Facial Recognition Attendance System is built on a **distributed client-server architecture** with specialized components for facial recognition, data management, and administration:

```
                    CLIENT LAYER
    ┌─────────────────────────────────────┐
    │   Desktop App (Python)              │
    │   Device 1: Captures & processes    │
    │   faces, connects to Flask API      │
    └────────┬────────────────────────────┘
             │
             │ HTTP/REST POST /recognize
             │ (Image upload)
             │
    ┌────────────────────────────────────────────────┐
    │   Mobile App (Android)                         │
    │   Device 2: Captures & processes               │
    │   faces, connects to Flask API                 │
    └────────┬─────────────────────────────────────┘
             │
             │ HTTP/REST POST /recognize
             │ (Image upload)
             │
             ▼
┌────────────────────────────────────────────────────────────┐
│                  FLASK API SERVER                           │
│          (Facial Recognition Engine)                        │
│  ├─ POST /recognize                                        │
│  │   ├─ Load image                                        │
│  │   ├─ Extract face embedding (FaceNet)                 │
│  │   ├─ Normalize (L2)                                    │
│  │   ├─ Compare with stored embeddings                   │
│  │   └─ Return similarity score                          │
│  │                                                         │
│  └─ Config: EMBEDDINGS_PATH, THRESHOLD, PORT 5000        │
└────────┬─────────────────────────────────────┬────────────┘
         │                                     │
         │ POST /insert_presence               │
         │ (Record attendance)                 │
         │                                     │
         ▼                                     ▼
    ┌─────────────────────────────────────────────────┐
    │      ADMIN PANEL (Web Interface)                │
    │                                                  │
    │  ┌──────────────────────────────────────────┐  │
    │  │  proj5.html (Admin Dashboard)            │  │
    │  │  ├─ Student Management                   │  │
    │  │  ├─ Professor Management                 │  │
    │  │  ├─ Module/Session Management            │  │
    │  │  └─ Attendance Reports                   │  │
    │  └──────────────────────────────────────────┘  │
    │                │                                │
    │                │ AJAX calls                     │
    │                ▼                                │
    │  ┌──────────────────────────────────────────┐  │
    │  │  PHP API Endpoints (backend/php/)        │  │
    │  │  ├─ /students/*                          │  │
    │  │  ├─ /professors/*                        │  │
    │  │  ├─ /modules/*                           │  │
    │  │  ├─ /sessions/*                          │  │
    │  │  ├─ /attendance/*                        │  │
    │  │  └─ /groups/*                            │  │
    │  └──────────────────────────────────────────┘  │
    └──────────┬──────────────────────────────────────┘
               │
               │ SQL queries
               ▼
        ┌────────────────────┐
        │   MySQL Database   │
        │      (l3db)        │
        │                    │
        │ ├─ utilisateurs    │
        │ ├─ etudiants       │
        │ ├─ professeurs     │
        │ ├─ modules         │
        │ ├─ seances         │
        │ ├─ presences       │
        │ ├─ niveaux         │
        │ └─ groupes         │
        └────────────────────┘
```

## Component Details

### 1. Client Applications Layer

#### 1.1 Desktop Client (Python) - Separate Device

**Responsibility**: Capture faces and send to Flask server

**Features**:
- Live camera feed
- Real-time face detection
- Image preprocessing (resize, quality check)
- API communication to Flask
- Offline queue support (optional)

**Communication**:
```python
# Pseudo-code
import requests
import cv2

cap = cv2.VideoCapture(0)  # Webcam
ret, frame = cap.read()
files = {'image': cv2.imencode('.jpg', frame)[1]}
response = requests.post('http://flask-server:5000/recognize', files=files)
result = response.json()  # {"identity": "Name", "similarity": 0.85, ...}
```

#### 1.2 Mobile Client (Android) - Separate Device

**Responsibility**: Capture faces via mobile camera, send to Flask server

**Features**:
- Camera integration (Camera2 API or CameraX)
- Image capture and compression
- Network connectivity checks
- Offline fallback support
- Result display to operator

**Communication**: Similar HTTP POST to `/recognize` endpoint

### 2. Frontend Layer (`frontend/`) - DEPRECATED

### 3. Flask API Server Layer

**Technology**: Python 3.12 + Flask

**Deployment**: Can run on same machine as MySQL or separate server

**Responsibilities**:
- Facial recognition service
- Image preprocessing
- Embedding comparison
- Result return to clients

#### 3.1 Core Components

**server.py - Main Flask Application**

```python
# Key Functions
@app.route('/recognize', methods=['POST'])
def recognize():
    # Load image
    # Extract face embedding
    # Compare with stored embeddings
    # Return: {"identity": "...", "similarity": ..., "status": "..."}

# Initialization
embedder = FaceNet()  # Pre-trained model
with open(EMBEDDINGS_PATH, 'rb') as f:
    embeddings = pickle.load(f)
    labels = [...]
```

**Embedding Generation** (`embe.py`):
- One-time setup script
- Processes dataset folder
- Generates FaceNet embeddings
- Saves pickle file

#### 3.2 Technical Processing

```
Image → OpenCV Load → Face Detection → FaceNet Extraction
  ↓         ↓               ↓                ↓
  JPEG    BGR to RGB   Aligned face    128-D embedding
  
                        ↓
                      L2 Normalize
                        ↓
                  Cosine Similarity (vs stored embeddings)
                        ↓
                   Return best match
```

### 4. Admin Web Panel Layer

**Technology**: HTML5/CSS3/JavaScript + PHP

**Deployment**: XAMPP or Apache web server

**User**: Administrators only

**Responsibilities**:
- Student management
- Professor management  
- Session scheduling
- Module management
- Attendance reporting
- Data configuration

**Interface**: proj5.html (Arabic-enabled admin dashboard)

**Backend API Endpoints** (`backend/php/`):
- Student CRUD operations
- Professor CRUD operations
- Module management
- Session creation
- Attendance recording (from Flask)
- Reports generation
- Group/Level management

### 5. Database Layer

**Technology**: MySQL 8.0+

**Database**: `l3db`

**Connection**: PDO or MySQLi from PHP

#### Database Schema

```sql
-- User Management
TABLE utilisateurs (All users: students, professors, admins)
  ├─ id_utilisateur (PK)
  ├─ nom, prenom (full name)
  ├─ email (UNIQUE)
  ├─ photo (BLOB optional)
  └─ type_user (student/professor/admin)

TABLE etudiants (Student-specific)
  ├─ id_etudiant (PK, FK→utilisateurs)
  ├─ matricule (UNIQUE student ID)
  ├─ id_niveau, id_groupe (FKs)
  ├─ password_hash
  └─ date_inscription

TABLE professeurs (Professor-specific)
  ├─ id_professeur (PK, FK→utilisateurs)
  ├─ specialite (subject)
  └─ department

-- Academic Management
TABLE niveaux (Levels)
  ├─ id_niveau (PK)
  ├─ nom_niveau (L1, L2, L3)
  └─ description

TABLE groupes (Groups)
  ├─ id_groupe (PK)
  ├─ nom_groupe
  ├─ id_niveau (FK)
  └─ capacite

TABLE modules (Courses)
  ├─ id_module (PK)
  ├─ nom_module
  ├─ code_module
  ├─ id_professeur (FK)
  └─ description

-- Session & Attendance
TABLE seances (Class Sessions)
  ├─ id_seance (PK)
  ├─ id_module (FK)
  ├─ id_professeur (FK)
  ├─ date_seance, heure_debut, heure_fin
  └─ statut (active/inactive)

TABLE presences (Attendance Records)
  ├─ id_presence (PK)
  ├─ id_etudiant (FK)
  ├─ id_seance (FK)
  ├─ date_reconnaissance (datetime)
  ├─ heure_reconnaissance
  └─ score_similarite (0.0 - 1.0)
```

## Performance Characteristics

### Timing Analysis

| Operation | Time | Notes |
|-----------|------|-------|
| Face Recognition | 50-100ms | Per image, FaceNet inference |
| Embedding Comparison | 10-20ms | Cosine similarity, vectorized |
| Database Insert | 30-50ms | MySQL write + indexing |
| API Response | 100-150ms | Total end-to-end |
| Embedding Generation | 200-300ms | Per student photo, one-time |

### Scalability

**Current Limitations**:
- Single Flask server (stateless, can be scaled)
- In-memory embedding storage (max ~10K students)
- MySQL single instance (can be replicated)

**Scaling Strategy**:
- Load balance Flask instances via NGINX/HAProxy
- Use Redis for embedding caching
- Implement database replication
- Consider distributed ML pipeline (GPUs)

## Security Considerations

### Current Implementation

**Authentication**: PHP session-based (basic)
**Authorization**: Role-based (student/professor)
**Data Protection**: Passwords stored (should be hashed)

### Recommendations

1. **API Security**
   - Implement JWT for API authentication
   - Rate limiting on endpoints
   - CORS configuration review

2. **Database Security**
   - Use prepared statements (already done)
   - Encrypt passwords with bcrypt/Argon2
   - Database access control

3. **Image Handling**
   - Validate file types
   - Limit image size
   - Scan for malicious content

4. **HTTPS/SSL**
   - Encrypt data in transit
   - Certificate management

## Deployment Architecture

### Development Setup
```
Developer Machine
├─ XAMPP (Apache + MySQL)
├─ Python 3.12 + Flask (port 5000)
├─ proj5.html (admin panel)
└─ Browser for admin testing
```

### Production Architecture
```
┌─────────────────────────────────────────────────────────┐
│                    CLIENT NETWORK                        │
│  ┌──────────────┐         ┌──────────────┐              │
│  │ Desktop      │         │ Mobile       │              │
│  │ App          │         │ App          │              │
│  │ (Python)     │         │ (Android)    │              │
│  │ IP: 192...   │         │ IP: 192...   │              │
│  └──────────────┘         └──────────────┘              │
│           │                        │                     │
└───────────┼────────────────────────┼─────────────────────┘
            │ HTTP POST              │
            │ /recognize             │
            └────────────┬───────────┘
                         │
            ┌────────────▼───────────┐
            │   FIREWALL/ROUTER      │
            │   Port forwarding:5000 │
            └────────────┬───────────┘
                         │
        ┌────────────────▼──────────────┐
        │   FLASK APP SERVER            │
        │   (Python)                    │
        │   Host: 0.0.0.0               │
        │   Port: 5000                  │
        │   ├─ /recognize endpoint      │
        │   └─ FaceNet model loaded     │
        └────────────┬──────────────────┘
                     │
    ┌────────────────┼──────────────────┐
    │                │                  │
    ▼                ▼                  ▼
┌────────────┐  ┌──────────┐  ┌──────────────┐
│ Admin      │  │ Embeddin │  │ Temp Images  │
│ Panel      │  │ gs       │  │ Storage      │
│ (HTML)     │  │ (*.pkl)  │  │ (optional)   │
└────────────┘  └──────────┘  └──────────────┘

        ┌──────────────────────┐
        │  ADMIN DASHBOARD     │
        │  (Web Browser)       │
        │  URL: /../proj5.html │
        └────────────┬─────────┘
                     │
                     │ AJAX/Forms
                     ▼
        ┌──────────────────────┐
        │  WEB SERVER (Apache) │
        │  Port 80/443         │
        │  ├─ proj5.html       │
        │  └─ /backend/php/    │
        └────────────┬─────────┘
                     │
                     │ SQL
                     ▼
        ┌──────────────────────┐
        │  DATABASE LAYER      │
        │  MySQL 8.0+          │
        │  Database: l3db      │
        └──────────────────────┘
```

### Scaling Strategy

**For 100+ Students**:
1. Use process manager (Gunicorn) for Flask
2. Load balance with NGINX
3. Database replication/clustering
4. Cache embeddings with Redis

**For 1000+ Students**:
1. GPU acceleration for embedding
2. Distributed inference (GPU cluster)
3. Database sharding
4. Message queue for async processing (RabbitMQ, Kafka)

**Example Production Docker Stack**:
```yaml
services:
  flask:
    image: facial-recognition:latest
    environment:
      - FLASK_PORT=5000
      - EMBEDDINGS_PATH=/models/embeddings.pkl
    ports:
      - "5000:5000"
    volumes:
      - ./models:/models
      
  nginx:
    image: nginx:latest
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      
  mysql:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=root
      - MYSQL_DATABASE=l3db
    volumes:
      - db-data:/var/lib/mysql
      
  php:
    image: php:8-fpm
    volumes:
      - ./backend/php:/var/www/html
```

## Technology Rationale

| Technology | Why Chosen | Alternatives |
|------------|-----------|--------------|
| FaceNet | State-of-art, pre-trained, 128-D effective | VGGFace2, ArcFace, ResNet |
| Flask | Lightweight, Python, easy REST API | Django, FastAPI |
| PHP | Rapid development, hosting compatibility | Node.js, Python (async) |
| MySQL | Structured data, reliability | PostgreSQL, MongoDB |
| OpenCV | Industry standard image processing | Pillow, scikit-image |
| Keras/TensorFlow | High-level API, model loading | PyTorch, ONNX |

---

**Last Updated**: February 2026
**Architecture Version**: 1.0 (MVP)
