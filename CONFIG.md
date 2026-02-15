# Database Configuration

Database Name: l3db
Host: localhost
User: root
Password: (empty for local development)

## Connection Details

For PHP (backend/php/):
```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "l3db";
```

For Python (backend/python/):
```python
DATABASE_URL = "mysql+pymysql://root:@localhost/l3db"
```

## Flask Configuration (backend/python/server.py)

```python
EMBEDDINGS_PATH = "C:\\xampp\\htdocs\\projet\\face_embeddings3.pkl"
UPLOAD_FOLDER = "C:\\Users\\ADMIN\\Documents\\projet\\server_images"
THRESHOLD = 0.6  # Face recognition similarity threshold
```

## Model Configuration

- FaceNet Model: `/models/facenet_keras.h5`
- Image Normalization: L2 norm
- Embedding Dimension: 128-D vectors
