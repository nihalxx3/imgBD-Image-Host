import os
import shutil

root_dir = os.path.dirname(os.path.dirname(__file__))
uploads_dir = os.path.join(root_dir, 'uploads')
nsfw_photo_dir = os.path.join(root_dir, 'nsfw', 'photo')
log_path = os.path.join(os.path.dirname(__file__), 'nsfwdb.log')

# Load whitelist
whitelist_path = os.path.join(os.path.dirname(__file__), 'whitelist.log')
if os.path.exists(whitelist_path):
    with open(whitelist_path, 'r') as wl:
        whitelist = set(line.strip() for line in wl if line.strip())
else:
    whitelist = set()

if not os.path.exists(nsfw_photo_dir):
    os.makedirs(nsfw_photo_dir)

with open(log_path, 'r') as log:
    for line in log:
        parts = line.strip().split('|')
        if len(parts) < 4:
            continue
        fname = parts[1].strip()
        status = parts[3].strip()
        if fname in whitelist:
            print(f"Skipping whitelisted file: {fname}")
            continue
        if status == 'nsfw':
            src = os.path.join(uploads_dir, fname)
            dst = os.path.join(nsfw_photo_dir, fname)
            if os.path.isfile(src) and not os.path.isfile(dst):
                shutil.copy2(src, dst)
                print(f"Copied: {fname} -> nsfw/photo/")
print("Done.")
