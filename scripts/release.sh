#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$repo_root"

version_file="src/version"
if [[ ! -f "$version_file" ]]; then
    echo "error: $version_file not found" >&2
    exit 1
fi

version="$(tr -d '[:space:]' < "$version_file")"
tag="v${version}"

if [[ -z "$version" ]]; then
    echo "error: $version_file is empty" >&2
    exit 1
fi

if [[ -n "$(git status --porcelain)" ]]; then
    echo "error: working tree is not clean, commit or stash changes first" >&2
    exit 1
fi

if git rev-parse "$tag" >/dev/null 2>&1; then
    echo "error: tag $tag already exists locally" >&2
    exit 1
fi

if git ls-remote --tags origin | grep -q "refs/tags/${tag}$"; then
    echo "error: tag $tag already exists on origin" >&2
    exit 1
fi

read -r -p "Deploy version ${tag} (commit $(git rev-parse --short HEAD))? [y/N] " confirm
if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
    echo "Aborted."
    exit 1
fi

echo "Tagging $tag at $(git rev-parse --short HEAD)"
git tag -a "$tag" -m "Release $tag"

echo "Pushing $tag to origin"
git push origin "$tag"

echo "Done: $tag pushed to origin"
