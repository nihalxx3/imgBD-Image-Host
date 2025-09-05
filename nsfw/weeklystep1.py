import os
from nudenet import NudeDetector
import datetime

root_dir = os.path.dirname(os.path.dirname(__file__))
uploads_dir = os.path.join(root_dir, 'uploads')
nsfw_photo_dir = os.path.join(root_dir, 'nsfw', 'photo')
log_path = os.path.join(os.path.dirname(__file__), 'weekly_nsfwdb.log')
detector = NudeDetector()
exts = ('.jpg', '.jpeg', '.png', '.gif')

print(f"Weekly scan of {uploads_dir} for images...")
log_lines = []
total = nsfw = safe = 0
for fname in os.listdir(uploads_dir):
    fpath = os.path.join(uploads_dir, fname)
    if os.path.isdir(fpath) or fname.lower().endswith('.md.') or fname == 'nsfw':
        continue
    if fname.lower().endswith(exts):
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
with open(log_path, 'a') as log:
    for line in log_lines:
        log.write(line + '\n')
    log.write(f"SUMMARY: {datetime.datetime.now()} | Total: {total} | NSFW: {nsfw} | Safe: {safe}\n\n")
print(f"Weekly scan complete. Total: {total}, NSFW: {nsfw}, Safe: {safe}")
