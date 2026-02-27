# ============================================================
# Face Embedding Extraction Module
# Author: Aknouche Mohamed Ayoub
# Description: Extracts and normalizes facial embeddings from
#              a dataset using FaceNet and saves them for
#              real-time attendance recognition via Flask API
# ============================================================

import os
import numpy as np
import cv2
import tensorflow as tf
from sklearn.preprocessing import Normalizer
import pickle
from keras_facenet import FaceNet

# Initialize FaceNet model for embedding extraction
embedder = FaceNet()

# L2 normalization for consistent embedding distances
l2_normalizer = Normalizer('l2')

# Path to dataset containing one folder per person
DATASET_PATH = "C:/Users/ADMIN/Desktop/dataset"

# Storage for embeddings and corresponding labels
embeddings = []
labels = []

# Process each person's folder in the dataset
for person_name in os.listdir(DATASET_PATH):
    person_path = os.path.join(DATASET_PATH, person_name)
    
    if not os.path.isdir(person_path):
        continue
    
    # Process each image for the current person
    for image_name in os.listdir(person_path):
        image_path = os.path.join(person_path, image_name)
        
        # Load and convert image to RGB for FaceNet
        img = cv2.imread(image_path)
        if img is None:
            continue
        img_rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
        
        # Extract face embedding with confidence threshold
        results = embedder.extract(img_rgb, threshold=0.95)
        if results:
            embedding = results[0]['embedding']
            # Normalize embedding using L2 normalization
            embedding = l2_normalizer.transform(
                np.expand_dims(embedding, axis=0)
            )[0]
            embeddings.append(embedding)
            labels.append(person_name)

# Persist embeddings and labels for inference
data = {'embeddings': embeddings, 'labels': labels}
with open("face_embeddings.pkl", "wb") as f:
    pickle.dump(data, f)

print("Embeddings extracted and saved successfully!")
