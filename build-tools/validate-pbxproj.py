#!/usr/bin/env python3
"""
Sanity-check the hand-authored iOS Xcode project file (project.pbxproj).

Because the iOS project was authored without Xcode (on Windows), its
project.pbxproj is hand-edited. A malformed pbxproj won't open in Xcode at all,
so this validates the structural invariants after every manual edit:

  * balanced {} and () delimiters
  * matched `/* Begin X section */` and `/* End X section */` markers
  * every referenced 24-hex object ID is actually defined (no dangling refs)

Exit code 0 = OK, 1 = problems found.

Usage:
    python build-tools/validate-pbxproj.py [path/to/project.pbxproj]
"""

import os
import re
import sys

DEFAULT = os.path.join(
    os.path.dirname(__file__),
    "..", "newsflow-ios", "NewsFlow.xcodeproj", "project.pbxproj",
)


def main() -> int:
    path = sys.argv[1] if len(sys.argv) > 1 else DEFAULT
    if not os.path.isfile(path):
        print(f"NOT FOUND: {path}")
        return 1

    with open(path, encoding="utf-8") as fh:
        src = fh.read()

    ok = True

    # 1) delimiter balance
    for op, cl in (("{", "}"), ("(", ")")):
        o, c = src.count(op), src.count(cl)
        status = "OK" if o == c else "MISMATCH"
        if o != c:
            ok = False
        print(f"{op}{cl}: open={o} close={c} {status}")

    # 2) defined vs referenced object IDs (24 hex chars)
    defined = set(re.findall(r"^\t\t([0-9A-F]{24}) ", src, re.M))
    refs = set(re.findall(r"\b([0-9A-F]{24})\b", src))
    missing = sorted(r for r in refs if r not in defined)
    print(f"defined objects: {len(defined)}")
    print(f"referenced ids:  {len(refs)}")
    print("referenced-but-undefined:", missing if missing else "NONE")
    if missing:
        ok = False

    # 3) section markers balanced
    begins = sorted(re.findall(r"/\* Begin (\w+) section \*/", src))
    ends = sorted(re.findall(r"/\* End (\w+) section \*/", src))
    status = "OK" if begins == ends else "MISMATCH"
    if begins != ends:
        ok = False
    print(f"Begin sections: {len(begins)}  End sections: {len(ends)}  {status}")

    print("\nRESULT:", "OK" if ok else "PROBLEMS FOUND")
    return 0 if ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
