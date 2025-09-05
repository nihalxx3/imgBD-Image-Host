import subprocess
import os

nsfw_dir = os.path.dirname(__file__)

# Run nsfwdb.py
print("Running nsfwdb.py...")
subprocess.run(["python", os.path.join(nsfw_dir, "nsfwdb.py")], check=True)

# Run move_nsfw.py
print("Running move_nsfw.py...")
subprocess.run(["python", os.path.join(nsfw_dir, "move_nsfw.py")], check=True)

print("Step 1 complete.")
