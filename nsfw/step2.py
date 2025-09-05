import os

root_dir = os.path.dirname(os.path.dirname(__file__))
uploads_dir = os.path.join(root_dir, 'uploads')
nsfw_photo_dir = os.path.join(root_dir, 'nsfw', 'photo')

delete_log = os.path.join(os.path.dirname(__file__), 'delete.log')
sql_log = os.path.join(os.path.dirname(__file__), 'cleandatabase.txt')
whitelist_log = os.path.join(os.path.dirname(__file__), 'whitelist.log')
nsfwdb_log = os.path.join(os.path.dirname(__file__), 'nsfwdb.log')
redlist_log = os.path.join(os.path.dirname(__file__), 'redlist.log')

# Get all files previously marked as nsfw
previous_nsfw = set()
with open(nsfwdb_log, 'r') as log:
    for line in log:
        parts = line.strip().split('|')
        if len(parts) < 4:
            continue
        fname = parts[1].strip()
        status = parts[3].strip()
        if status == 'nsfw':
            previous_nsfw.add(fname)

# Get current files in nsfw/photo
current_files = set(f for f in os.listdir(nsfw_photo_dir) if os.path.isfile(os.path.join(nsfw_photo_dir, f)))

# Files that were previously marked nsfw but are now deleted (whitelisted)
whitelisted = sorted(list(previous_nsfw - current_files))

files = [f for f in current_files if f != '.htaccess']
deleted = []
sql_names = []


# Prepare redlist entries
redlist_entries = []
nsfw_scores = {}
# Read scores from nsfwdb.log
with open(nsfwdb_log, 'r') as log:
    for line in log:
        parts = line.strip().split('|')
        if len(parts) < 4:
            continue
        fname_score = parts[1].strip()
        score_part = parts[2].strip().replace('score: ', '')
        status_part = parts[3].strip()
        nsfw_scores[fname_score] = (score_part, status_part)

for fname in files:
    # Add to redlist
    score, status = nsfw_scores.get(fname, ("N/A", "N/A"))
    redlist_entries.append(f"{fname}: score={score} | {status}")
    # Delete from uploads/ if present
    src = os.path.join(uploads_dir, fname)
    if os.path.isfile(src):
        os.remove(src)
        deleted.append(fname)
    # Only add non-md files for SQL
    if '.md.' not in fname:
        sql_names.append(fname)


# Write deleted file names to delete.log
with open(delete_log, 'w') as f:
    for fname in deleted:
        f.write(fname + '\n')

# Append to redlist.log
with open(redlist_log, 'a') as f:
    for entry in redlist_entries:
        f.write(entry + '\n')

# Write whitelisted file names to whitelist.log
with open(whitelist_log, 'w') as f:
    for fname in whitelisted:
        f.write(fname + '\n')

# Write SQL query to cleandatabase.txt
if sql_names:
    sql = "DELETE FROM `images` WHERE stored_name IN ("
    sql += ", ".join(f"'{name}'" for name in sql_names)
    sql += ");\n"
else:
    sql = "-- No files to delete\n"

with open(sql_log, 'w') as f:
    f.write(sql)

print("Step 2 complete. Deleted files listed in delete.log. Whitelisted files in whitelist.log. SQL query in cleandatabase.txt.")
