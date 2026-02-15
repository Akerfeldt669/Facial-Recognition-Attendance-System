from flask import Flask, request, jsonify
import numpy as np
import os
import uuid
import pickle
import cv2
from sklearn.preprocessing import Normalizer
from sklearn.metrics.pairwise import cosine_similarity
from keras_facenet import FaceNet
import requests  

app = Flask(__name__)

# Initialize FaceNet
embedder = FaceNet()
l2_normalizer = Normalizer('l2')

# Paths
EMBEDDINGS_PATH = "C:\\xampp\\htdocs\\projet\\face_embeddings3.pkl"
UPLOAD_FOLDER = "C:\\Users\\ADMIN\\Documents\\projet\\server_images"
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

# Load stored embeddings and labels
with open(EMBEDDINGS_PATH, "rb") as f:
    data = pickle.load(f)

embeddings = data['embeddings']
labels = data['labels']

# Similarity threshold
THRESHOLD = 0.6

def recognize_face(embedding, threshold=THRESHOLD):
    similarities = cosine_similarity([embedding], embeddings)[0]
    best_match_index = np.argmax(similarities)
    best_match_score = similarities[best_match_index]

    if best_match_score >= threshold:
        return labels[best_match_index], best_match_score
    else:
        return "Unknown", best_match_score

@app.route('/recognize', methods=['POST'])
def recognize():
    try:
        if "image" not in request.files:
            return jsonify({"error": "No image uploaded"}), 400

        file = request.files["image"]
        if file.filename == "":
            return jsonify({"error": "Empty filename"}), 400

        # Save uploaded image
        filename = f"{uuid.uuid4().hex}.jpg"
        image_path = os.path.join(UPLOAD_FOLDER, filename)
        file.save(image_path)

        # Load and convert image
        img = cv2.imread(image_path)
        if img is None:
            return jsonify({"error": "Failed to read image"}), 400

        img_rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)

        # Detect and extract embedding
        results = embedder.extract(img_rgb, threshold=0.95)

        if not results or 'embedding' not in results[0]:
            return jsonify({"error": "No valid face embedding detected"}), 400

        new_embedding = results[0]['embedding']
        new_embedding = l2_normalizer.transform(np.expand_dims(new_embedding, axis=0))[0]

        # Recognize face
        identity, score = recognize_face(new_embedding)

        # Default PHP feedback
        php_feedback = "Recognition complete. No PHP feedback received."

        if identity != "Unknown":
            try:
                php_url = "http://localhost/projet/insert_presence.php"
                payload = {
                    "identity": identity,
                    "similarity": float(score),
                    "status": "known"
                }
                response = requests.post(php_url, data=payload)
                php_data = response.json()
                php_feedback = php_data.get("message") or php_data.get("error", php_feedback)
                print("[SYNC] PHP Response:", php_feedback)
            except Exception as sync_error:
                php_feedback = f"Failed to sync with PHP: {str(sync_error)}"
                print("[SYNC ERROR]", sync_error)

        return jsonify({
            "status": "known" if identity != "Unknown" else "unknown",
            "identity": identity,
            "similarity": float(score),
            "message": php_feedback,
            "image_path": image_path
        })

    except Exception as e:
        print("[SERVER ERROR]:", str(e))
        return jsonify({"error": "Internal server error"}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
