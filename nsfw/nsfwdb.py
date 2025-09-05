import os
from nudenet import NudeDetector
import datetime
import cv2

exts = ('.jpg', '.jpeg', '.png', '.gif')
root_dir = os.path.dirname(os.path.dirname(__file__))
uploads_dir = os.path.join(root_dir, 'uploads')
nsfw_photo_dir = os.path.join(root_dir, 'nsfw', 'photo')
detector = NudeDetector()

# Load whitelist
whitelist_path = os.path.join(os.path.dirname(__file__), 'whitelist.log')
if os.path.exists(whitelist_path):
    with open(whitelist_path, 'r') as wl:
        whitelist = set(line.strip() for line in wl if line.strip())
else:
    whitelist = set()

exts = ('.jpg', '.jpeg', '.png', '.gif')

print(f"Scanning {uploads_dir} for images...")
log_lines = []
total = nsfw = safe = skipped = 0
for fname in os.listdir(uploads_dir):
    fpath = os.path.join(uploads_dir, fname)
    if os.path.isdir(fpath) or fname.lower().endswith('.md.') or fname == 'nsfw':
        continue
    if fname in whitelist:
        print(f"Skipping whitelisted file: {fname}")
        skipped += 1
        continue
    if fname.lower().endswith(exts):
        img = cv2.imread(fpath)
        if img is None:
            print(f"Skipping invalid image: {fname}")
            continue
        result = detector.detect(fpath)
        score = max([r['score'] for r in result], default=0.0)
        status = 'nsfw' if score > 0.5 else 'safe'
        print(f"{fname}: score={score:.3f} | {status}")
        log_lines.append(f"{datetime.datetime.now()} | {fname} | score: {score:.3f} | {status}")
        total += 1
        if status == 'nsfw':
            nsfw += 1
        else:
            safe += 1
with open(os.path.join(os.path.dirname(__file__), 'nsfwdb.log'), 'a') as log:
    for line in log_lines:
        log.write(line + '\n')
    log.write(f"SUMMARY: {datetime.datetime.now()} | Total: {total} | NSFW: {nsfw} | Safe: {safe} | Skipped: {skipped}\n\n")
print(f"Scan complete. Total: {total}, NSFW: {nsfw}, Safe: {safe}, Skipped: {skipped}")
