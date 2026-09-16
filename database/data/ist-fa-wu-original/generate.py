#!/usr/bin/env python3
"""
Generates original (non-copied) SVG artwork + a question manifest for the
IST FA (Figurenauswahl) and WU (Wurfelaufgaben) subtests, expanded to 20
scored questions each (real-world numbering 117-136 for FA, 137-156 for WU)
to match the already-seeded RW 0-20 norm tables (ist_norm_subtests).

Content is deliberately original artwork (simple polygons for FA, abstract
face patterns for WU) - it reuses only the answer-key *pattern* confirmed by
the operator (which shape/cube letter is correct per question number), not
any imagery from the real Amthauer IST test booklet, per the project's
existing content_origin=original_internal / copyright_status=internally_authored
convention for this subtest pair.

Run: python3 generate.py
Output: media/fa/*.svg, media/wu/*.svg, manifest.json (all relative to this file).
"""
import json
import math
import os

BASE = os.path.dirname(os.path.abspath(__file__))
FA_DIR = os.path.join(BASE, "media", "fa")
WU_DIR = os.path.join(BASE, "media", "wu")

STROKE = "#292524"
FILL = "#44403c"
BG_FILL = "#f7f3e8"

# ---------------------------------------------------------------------------
# FA: figure-assembly puzzles
# ---------------------------------------------------------------------------

FA_ANSWER_KEY = {
    117: "A", 118: "C", 119: "B", 120: "A", 121: "D", 122: "B", 123: "C",
    124: "E", 125: "E", 126: "D", 127: "E", 128: "B",
    129: "D", 130: "C", 131: "B", 132: "A", 133: "B", 134: "D", 135: "C", 136: "C",
}

# Each shape: ordered vertex list (a simple polygon) + a cut describing how it
# splits losslessly into two fragment polygons (either a vertex-to-vertex
# diagonal, given as a pair of vertex indices, or -- for triangles, which have
# no diagonal -- a cevian from one vertex to the midpoint of the opposite edge).
FA_SET_1 = {
    "A": {"vertices": [(50, 5), (90, 38), (75, 88), (25, 88), (10, 38)], "cut": ("diag", 1, 3)},
    "B": {"vertices": [(30, 5), (70, 5), (95, 50), (70, 95), (30, 95), (5, 50)], "cut": ("diag", 0, 3)},
    "C": {"vertices": [(30, 10), (70, 10), (95, 90), (5, 90)], "cut": ("diag", 0, 2)},
    "D": {"vertices": [(50, 5), (85, 45), (50, 95), (15, 45)], "cut": ("diag", 0, 2)},
    "E": {"vertices": [(20, 95), (20, 45), (50, 10), (80, 45), (80, 95)], "cut": ("diag", 1, 3)},
}

FA_SET_2 = {
    "A": {"vertices": [(25, 15), (90, 15), (75, 90), (10, 90)], "cut": ("diag", 0, 2)},
    "B": {"vertices": [(10, 10), (70, 10), (70, 90), (10, 60)], "cut": ("diag", 0, 2)},
    "C": {"vertices": [(15, 90), (95, 70), (40, 10)], "cut": ("cevian", 2, 0, 1)},
    "D": {"vertices": [(10, 20), (60, 20), (90, 50), (60, 80), (10, 80)], "cut": ("diag", 1, 3)},
    "E": {"vertices": [(10, 90), (10, 15), (90, 90)], "cut": ("cevian", 2, 0, 1)},
}

FA_ROTATIONS = [37, 52, 68, 83, 97, 112, 128, 143, 158, 172,
                187, 203, 218, 233, 248, 262, 277, 293, 308, 323]


def polygon_centroid(points):
    cx = sum(p[0] for p in points) / len(points)
    cy = sum(p[1] for p in points) / len(points)
    return cx, cy


def rotate_point(p, origin, degrees):
    ox, oy = origin
    x, y = p[0] - ox, p[1] - oy
    rad = math.radians(degrees)
    cos_a, sin_a = math.cos(rad), math.sin(rad)
    return (x * cos_a - y * sin_a + ox, x * sin_a + y * cos_a + oy)


def translate_point(p, dx, dy):
    return (p[0] + dx, p[1] + dy)


def split_shape(shape):
    verts = shape["vertices"]
    cut = shape["cut"]
    if cut[0] == "diag":
        i, j = cut[1], cut[2]
        piece_a = verts[i:j + 1]
        piece_b = verts[j:] + verts[:i + 1]
        return piece_a, piece_b
    if cut[0] == "cevian":
        apex_idx, edge_i, edge_j = cut[1], cut[2], cut[3]
        p1, p2 = verts[edge_i], verts[edge_j]
        mid = ((p1[0] + p2[0]) / 2, (p1[1] + p2[1]) / 2)
        apex = verts[apex_idx]
        piece_a = [apex, p1, mid]
        piece_b = [apex, mid, p2]
        return piece_a, piece_b
    raise ValueError(f"unknown cut kind {cut[0]}")


def points_attr(points):
    return " ".join(f"{x:.2f},{y:.2f}" for x, y in points)


def svg_wrap(width, height, body, title):
    return (
        f'<svg xmlns="http://www.w3.org/2000/svg" width="{width}" height="{height}" '
        f'viewBox="0 0 {width} {height}" role="img" aria-labelledby="title">'
        f'<title id="title">{title}</title>{body}</svg>\n'
    )


def render_option_shape(shape):
    poly = f'<polygon points="{points_attr(shape["vertices"])}" fill="{FILL}" stroke="{STROKE}" stroke-width="2" stroke-linejoin="round"/>'
    return svg_wrap(150, 150, poly, "Pilihan bentuk utuh")


def render_prompt(shape, seed_index):
    piece_a, piece_b = split_shape(shape)

    angle_a = FA_ROTATIONS[seed_index % len(FA_ROTATIONS)]
    angle_b = FA_ROTATIONS[(seed_index + 7) % len(FA_ROTATIONS)]

    def place(piece, angle, target_cx, target_cy):
        centroid = polygon_centroid(piece)
        rotated = [rotate_point(p, centroid, angle) for p in piece]
        rc = polygon_centroid(rotated)
        dx, dy = target_cx - rc[0], target_cy - rc[1]
        return [translate_point(p, dx, dy) for p in rotated]

    placed_a = place(piece_a, angle_a, 85, 90)
    placed_b = place(piece_b, angle_b, 235, 90)

    body = (
        f'<polygon points="{points_attr(placed_a)}" fill="{FILL}" stroke="{STROKE}" stroke-width="2" stroke-linejoin="round"/>'
        f'<polygon points="{points_attr(placed_b)}" fill="{FILL}" stroke="{STROKE}" stroke-width="2" stroke-linejoin="round"/>'
    )
    return svg_wrap(320, 180, body, "Potongan bentuk yang harus disusun")


def generate_fa():
    manifest = {"instruction": "Pilih bentuk utuh yang dapat disusun menggunakan setiap potongan tepat satu kali. Potongan boleh diputar, tidak boleh dicerminkan.", "questions": []}

    for key, shape in FA_SET_1.items():
        path = os.path.join(FA_DIR, f"fa-optionset1-{key.lower()}.svg")
        with open(path, "w") as f:
            f.write(render_option_shape(shape))
    for key, shape in FA_SET_2.items():
        path = os.path.join(FA_DIR, f"fa-optionset2-{key.lower()}.svg")
        with open(path, "w") as f:
            f.write(render_option_shape(shape))

    numbers = list(range(117, 137))
    for idx, number in enumerate(numbers):
        option_set = FA_SET_1 if number <= 128 else FA_SET_2
        set_name = "optionset1" if number <= 128 else "optionset2"
        correct = FA_ANSWER_KEY[number]
        shape = option_set[correct]

        prompt_svg = render_prompt(shape, idx)
        prompt_path = os.path.join(FA_DIR, f"fa-q{number}-prompt.svg")
        with open(prompt_path, "w") as f:
            f.write(prompt_svg)

        manifest["questions"].append({
            "question_number": number,
            "prompt_image": f"fa/fa-q{number}-prompt.svg",
            "correct_option": correct,
            "options": [
                {"key": k, "image": f"fa/fa-{set_name}-{k.lower()}.svg"}
                for k in ["A", "B", "C", "D", "E"]
            ],
        })

    return manifest


# ---------------------------------------------------------------------------
# WU: cube-rotation puzzles
# ---------------------------------------------------------------------------

WU_ANSWER_KEY = {
    137: "A", 138: "C", 139: "D", 140: "E", 141: "A",
    142: "C", 143: "D", 144: "C", 145: "E", 146: "A",
    147: "B", 148: "D", 149: "E", 150: "B", 151: "D",
    152: "B", 153: "A", 154: "E", 155: "B", 156: "C",
}

CUBE_TOP = [(125, 23), (183, 43), (124, 70), (64, 50)]
CUBE_FRONT = [(64, 50), (124, 70), (124, 145), (64, 126)]
CUBE_RIGHT = [(124, 70), (183, 43), (183, 116), (124, 145)]


def face_centroid(face):
    return polygon_centroid(face)


def icon_dot(cx, cy):
    return f'<circle cx="{cx}" cy="{cy}" r="7" fill="{STROKE}"/>'


def icon_checkerboard(cx, cy):
    s = 9
    out = ""
    for i, (dx, dy) in enumerate([(-s, -s), (0, -s), (-s, 0), (0, 0)]):
        fill = STROKE if i in (0, 3) else "none"
        out += f'<rect x="{cx+dx:.1f}" y="{cy+dy:.1f}" width="{s}" height="{s}" fill="{fill}" stroke="{STROKE}" stroke-width="1"/>'
    return out


def icon_diagonal_split(cx, cy):
    s = 11
    pts = f"{cx-s:.1f},{cy-s:.1f} {cx+s:.1f},{cy-s:.1f} {cx-s:.1f},{cy+s:.1f}"
    return (
        f'<rect x="{cx-s:.1f}" y="{cy-s:.1f}" width="{2*s}" height="{2*s}" fill="none" stroke="{STROKE}" stroke-width="1.5"/>'
        f'<polygon points="{pts}" fill="{STROKE}"/>'
    )


def icon_solid_square(cx, cy):
    s = 10
    return f'<rect x="{cx-s:.1f}" y="{cy-s:.1f}" width="{2*s}" height="{2*s}" fill="{STROKE}"/>'


def icon_crosshatch(cx, cy):
    s = 11
    lines = ""
    for off in (-s, 0, s):
        lines += f'<line x1="{cx-s:.1f}" y1="{cy+off:.1f}" x2="{cx+s:.1f}" y2="{cy+off:.1f}" stroke="{STROKE}" stroke-width="1.6"/>'
        lines += f'<line x1="{cx+off:.1f}" y1="{cy-s:.1f}" x2="{cx+off:.1f}" y2="{cy+s:.1f}" stroke="{STROKE}" stroke-width="1.6"/>'
    return lines


def icon_stripes_h(cx, cy):
    s = 11
    lines = ""
    for off in (-s * 0.6, 0, s * 0.6):
        lines += f'<line x1="{cx-s:.1f}" y1="{cy+off:.1f}" x2="{cx+s:.1f}" y2="{cy+off:.1f}" stroke="{STROKE}" stroke-width="2"/>'
    return lines


def icon_stripes_v(cx, cy):
    s = 11
    lines = ""
    for off in (-s * 0.6, 0, s * 0.6):
        lines += f'<line x1="{cx+off:.1f}" y1="{cy-s:.1f}" x2="{cx+off:.1f}" y2="{cy+s:.1f}" stroke="{STROKE}" stroke-width="2"/>'
    return lines


def icon_four_dots(cx, cy):
    s = 9
    out = ""
    for dx, dy in [(-s, -s), (s, -s), (-s, s), (s, s)]:
        out += f'<circle cx="{cx+dx:.1f}" cy="{cy+dy:.1f}" r="3.2" fill="{STROKE}"/>'
    return out


def icon_ring(cx, cy):
    return f'<circle cx="{cx}" cy="{cy}" r="9" fill="none" stroke="{STROKE}" stroke-width="2.4"/>'


def icon_single_diagonal(cx, cy):
    s = 11
    return f'<line x1="{cx-s:.1f}" y1="{cy-s:.1f}" x2="{cx+s:.1f}" y2="{cy+s:.1f}" stroke="{STROKE}" stroke-width="2.4"/>'


def icon_two_dots(cx, cy):
    return (
        f'<circle cx="{cx-8:.1f}" cy="{cy:.1f}" r="4.4" fill="{STROKE}"/>'
        f'<circle cx="{cx+8:.1f}" cy="{cy:.1f}" r="4.4" fill="{STROKE}"/>'
    )


def icon_triangle(cx, cy):
    s = 10
    pts = f"{cx:.1f},{cy-s:.1f} {cx+s:.1f},{cy+s:.1f} {cx-s:.1f},{cy+s:.1f}"
    return f'<polygon points="{pts}" fill="{STROKE}"/>'


def icon_plus(cx, cy):
    s = 10
    w = 3.4
    return (
        f'<rect x="{cx-w/2:.1f}" y="{cy-s:.1f}" width="{w}" height="{2*s}" fill="{STROKE}"/>'
        f'<rect x="{cx-s:.1f}" y="{cy-w/2:.1f}" width="{2*s}" height="{w}" fill="{STROKE}"/>'
    )


def icon_x(cx, cy):
    s = 9
    return (
        f'<line x1="{cx-s:.1f}" y1="{cy-s:.1f}" x2="{cx+s:.1f}" y2="{cy+s:.1f}" stroke="{STROKE}" stroke-width="2.6"/>'
        f'<line x1="{cx-s:.1f}" y1="{cy+s:.1f}" x2="{cx+s:.1f}" y2="{cy-s:.1f}" stroke="{STROKE}" stroke-width="2.6"/>'
    )


def icon_square_outline(cx, cy):
    s = 9
    return f'<rect x="{cx-s:.1f}" y="{cy-s:.1f}" width="{2*s}" height="{2*s}" fill="none" stroke="{STROKE}" stroke-width="2.4"/>'


WU_TYPES = {
    "A": (icon_dot, icon_checkerboard, icon_diagonal_split),
    "B": (icon_solid_square, icon_crosshatch, icon_stripes_h),
    "C": (icon_stripes_v, icon_four_dots, icon_ring),
    "D": (icon_single_diagonal, icon_two_dots, icon_triangle),
    "E": (icon_plus, icon_x, icon_square_outline),
}


def render_cube(top_icon, front_icon, right_icon, title):
    top_c = face_centroid(CUBE_TOP)
    front_c = face_centroid(CUBE_FRONT)
    right_c = face_centroid(CUBE_RIGHT)

    body = (
        f'<g stroke="{STROKE}" stroke-width="3" stroke-linejoin="round" vector-effect="non-scaling-stroke">'
        f'<polygon points="{points_attr(CUBE_TOP)}" fill="{BG_FILL}"/>'
        f'<polygon points="{points_attr(CUBE_FRONT)}" fill="{BG_FILL}"/>'
        f'<polygon points="{points_attr(CUBE_RIGHT)}" fill="{BG_FILL}"/>'
        f'</g>'
        f'{top_icon(*top_c)}'
        f'{front_icon(*front_c)}'
        f'{right_icon(*right_c)}'
    )
    return svg_wrap(240, 180, body, title)


def generate_wu():
    manifest = {"instruction": "Tentukan kubus acuan (A-E) mana yang merupakan hasil rotasi kubus pada soal, tanpa pencerminan.", "questions": []}

    for key, (t, f_, r) in WU_TYPES.items():
        svg = render_cube(t, f_, r, f"Kubus acuan {key}")
        with open(os.path.join(WU_DIR, f"wu-ref-{key.lower()}.svg"), "w") as fh:
            fh.write(svg)

    numbers = list(range(137, 157))
    per_letter_counter = {k: 0 for k in WU_TYPES}

    for number in numbers:
        correct = WU_ANSWER_KEY[number]
        t_icon, f_icon, r_icon = WU_TYPES[correct]
        shift_forward = per_letter_counter[correct] % 2 == 0
        per_letter_counter[correct] += 1

        if shift_forward:
            # proper rotation: right->top, top->front, front->right
            new_top, new_front, new_right = r_icon, t_icon, f_icon
        else:
            # the inverse proper rotation about the same axis
            new_top, new_front, new_right = f_icon, r_icon, t_icon

        svg = render_cube(new_top, new_front, new_right, f"Kubus soal nomor {number}")
        with open(os.path.join(WU_DIR, f"wu-q{number}.svg"), "w") as fh:
            fh.write(svg)

        manifest["questions"].append({
            "question_number": number,
            "prompt_image": f"wu/wu-q{number}.svg",
            "correct_option": correct,
            "options": [
                {"key": k, "image": f"wu/wu-ref-{k.lower()}.svg"}
                for k in ["A", "B", "C", "D", "E"]
            ],
        })

    return manifest


def main():
    os.makedirs(FA_DIR, exist_ok=True)
    os.makedirs(WU_DIR, exist_ok=True)

    manifest = {
        "fa": generate_fa(),
        "wu": generate_wu(),
    }

    with open(os.path.join(BASE, "manifest.json"), "w") as f:
        json.dump(manifest, f, indent=2, ensure_ascii=False)

    print(f"FA questions: {len(manifest['fa']['questions'])}")
    print(f"WU questions: {len(manifest['wu']['questions'])}")


if __name__ == "__main__":
    main()
