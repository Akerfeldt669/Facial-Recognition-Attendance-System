import os
import numpy as np
import cv2
import tensorflow as tf
from sklearn.preprocessing import Normalizer
import pickle
from keras_facenet import FaceNet

# Initialize FaceNet
embedder = FaceNet()
l2_normalizer = Normalizer('l2')

# Path to your dataset
DATASET_PATH = "C:/Users/ADMIN/Desktop/dataset"

# Lists to store embeddings and labels
embeddings = []
labels = []

# Loop through each person
for person_name in os.listdir(DATASET_PATH):
    person_path = os.path.join(DATASET_PATH, person_name)
    
    if not os.path.isdir(person_path):
        continue
    
    # Loop through each image
    for image_name in os.listdir(person_path):
        image_path = os.path.join(person_path, image_name)
        
        # Read image
        img = cv2.imread(image_path)
        if img is None:
            continue

        img_rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
        results = embedder.extract(img_rgb, threshold=0.95)

        if results:
            embedding = results[0]['embedding']
            embedding = l2_normalizer.transform(np.expand_dims(embedding, axis=0))[0]
            embeddings.append(embedding)
            labels.append(person_name)

# Save embeddings and labels to a file
data = {'embeddings': embeddings, 'labels': labels}

with open("face_embeddings.pkl", "wb") as f:
    pickle.dump(data, f)

print(" Embeddings extracted and saved!")
