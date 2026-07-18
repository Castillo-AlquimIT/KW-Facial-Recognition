try:
    import cv2
except ModuleNotFoundError:
    print("Error: OpenCV (cv2) is not installed. Install with `pip install opencv-contrib-python`.")
    exit(1)

import os
import base64
import numpy as np
from flask import Flask, request, jsonify

app = Flask(__name__)

DATASET_DIR = "dataset"
MODEL_FILE = "trained_model.yml"
LABELS_FILE = "labels.txt"

# How confident a match needs to be (LBPH: LOWER = more confident/similar)
RECOGNIZE_THRESHOLD = 70
# Stricter threshold used only to block duplicate registration —
# tighter than RECOGNIZE_THRESHOLD so we don't block borderline/ambiguous
# matches, only faces that are clearly already registered.
DUPLICATE_THRESHOLD = 55

os.makedirs(DATASET_DIR, exist_ok=True)

face_cascade = cv2.CascadeClassifier(
    cv2.data.haarcascades + "haarcascade_frontalface_default.xml"
)
recognizer = cv2.face.LBPHFaceRecognizer_create()

# ---------- helpers ----------

def load_labels():
    labels = {}
    if os.path.exists(LABELS_FILE):
        with open(LABELS_FILE, "r") as f:
            for line in f:
                idx, name = line.strip().split(",", 1)
                labels[int(idx)] = name
    return labels

def save_labels(labels):
    with open(LABELS_FILE, "w") as f:
        for idx, name in labels.items():
            f.write(f"{idx},{name}\n")

def decode_image(b64_string):
    img_data = base64.b64decode(b64_string.split(",")[-1])
    np_arr = np.frombuffer(img_data, np.uint8)
    return cv2.imdecode(np_arr, cv2.IMREAD_COLOR)

def train_model():
    labels = load_labels()
    if not labels:
        return False

    faces, ids = [], []
    for idx, name in labels.items():
        person_dir = os.path.join(DATASET_DIR, name)
        if not os.path.isdir(person_dir):
            continue
        for img_name in os.listdir(person_dir):
            img = cv2.imread(os.path.join(person_dir, img_name), cv2.IMREAD_GRAYSCALE)
            if img is None:
                continue
            faces.append(img)
            ids.append(idx)

    if not faces:
        return False

    recognizer.train(faces, np.array(ids))
    recognizer.save(MODEL_FILE)
    return True

def get_model_ready():
    labels = load_labels()
    if os.path.exists(MODEL_FILE) and labels:
        recognizer.read(MODEL_FILE)
        return True, labels
    return False, labels

def find_existing_match(face_img):
    """
    Returns (name, confidence) if face_img confidently matches an already
    registered person, otherwise None. Used to block duplicate registration.
    """
    model_ready, labels = get_model_ready()
    if not model_ready:
        return None

    pred_id, confidence = recognizer.predict(face_img)
    if confidence < DUPLICATE_THRESHOLD and pred_id in labels:
        return labels[pred_id], confidence
    return None

# ---------- routes ----------

@app.route("/register", methods=["POST"])
def register():
    body = request.json
    name = (body.get("name") or "").strip()
    image_b64 = body.get("image")

    if not name:
        return jsonify({"success": False, "message": "Name is required"}), 400
    if not image_b64:
        return jsonify({"success": False, "message": "Image is required"}), 400

    frame = decode_image(image_b64)
    gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
    faces = face_cascade.detectMultiScale(gray, 1.1, 5, minSize=(30, 30))

    if len(faces) == 0:
        return jsonify({"success": False, "message": "No face detected"}), 400

    (x, y, w, h) = faces[0]
    face_img = cv2.resize(gray[y:y + h, x:x + w], (200, 200))

    # --- Duplicate face check ---
    match = find_existing_match(face_img)
    if match is not None:
        matched_name, confidence = match
        return jsonify({
            "success": False,
            "message": "You already been register",
            "matched_name": matched_name,
            "confidence": float(confidence)
        }), 409

    labels = load_labels()
    existing_id = next((idx for idx, n in labels.items() if n == name), None)
    if existing_id is None:
        existing_id = max(labels.keys(), default=-1) + 1
        labels[existing_id] = name
        save_labels(labels)

    person_dir = os.path.join(DATASET_DIR, name)
    os.makedirs(person_dir, exist_ok=True)

    existing_count = len(os.listdir(person_dir))
    img_path = os.path.join(person_dir, f"{existing_count}.jpg")
    cv2.imwrite(img_path, face_img)

    trained = train_model()

    return jsonify({
        "success": True,
        "message": f"Face sample saved for '{name}' ({existing_count + 1} total). Trained: {trained}"
    })

@app.route("/recognize", methods=["POST"])
def recognize():
    body = request.json
    image_b64 = body.get("image")

    if not image_b64:
        return jsonify({"success": False, "message": "Image is required"}), 400

    model_ready, labels = get_model_ready()
    if not model_ready:
        return jsonify({"success": False, "message": "No trained model yet"}), 404

    frame = decode_image(image_b64)
    gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
    faces = face_cascade.detectMultiScale(gray, 1.1, 5, minSize=(30, 30))

    if len(faces) == 0:
        return jsonify({"success": False, "message": "No face detected"}), 400

    (x, y, w, h) = faces[0]
    face_img = cv2.resize(gray[y:y + h, x:x + w], (200, 200))
    pred_id, confidence = recognizer.predict(face_img)

    if confidence < RECOGNIZE_THRESHOLD and pred_id in labels:
        return jsonify({
            "success": True,
            "name": labels[pred_id],
            "confidence": float(confidence)
        })

    return jsonify({"success": False, "message": "Face not recognized", "confidence": float(confidence)}), 404

if __name__ == "__main__":
    app.run(port=5000, debug=True)
