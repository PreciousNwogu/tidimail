from collections import deque
from pathlib import Path

from PIL import Image

root = Path(__file__).resolve().parents[1]
src = Image.open(root / "public" / "logo.jpeg").convert("RGBA")
px = src.load()
w, h = src.size


def is_bg(pixel):
    r, g, b, _ = pixel
    return r >= 236 and g >= 236 and b >= 236


seen = bytearray(w * h)
queue = deque()


def enqueue(x, y):
    if x < 0 or y < 0 or x >= w or y >= h:
        return
    i = y * w + x
    if seen[i]:
        return
    if not is_bg(px[x, y]):
        return
    seen[i] = 1
    queue.append((x, y))


for x in range(w):
    enqueue(x, 0)
    enqueue(x, h - 1)
for y in range(h):
    enqueue(0, y)
    enqueue(w - 1, y)

clear = (255, 255, 255, 0)
while queue:
    x, y = queue.popleft()
    px[x, y] = clear
    enqueue(x - 1, y)
    enqueue(x + 1, y)
    enqueue(x, y - 1)
    enqueue(x, y + 1)

alpha = src.split()[-1]
box = alpha.getbbox()
if not box:
    raise SystemExit("logo punch failed: nothing left")

pad = max(8, int((box[2] - box[0]) * 0.04))
left = max(0, box[0] - pad)
top = max(0, box[1] - pad)
right = min(w, box[2] + pad)
bottom = min(h, box[3] + pad)
src.crop((left, top, right, bottom)).save(root / "public" / "logo.png")
print("wrote public/logo.png")
