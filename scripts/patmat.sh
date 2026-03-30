#!/usr/bin/bash
set -euo pipefail

if [ "$#" -ne 2 ]; then
    echo "Usage: bash patmat.sh <input_dir> <output_dir>" >&2
    exit 1
fi

input_dir="$1"
output_dir="$2"
patmat_bin="/usr/bin/patmatmotifs"

if [ ! -d "$input_dir" ]; then
    echo "Error: input directory not found: $input_dir" >&2
    exit 1
fi

mkdir -p "$output_dir"

shopt -s nullglob
files=("$input_dir"/*.fasta)

if [ "${#files[@]}" -eq 0 ]; then
    echo "Error: no FASTA files found in $input_dir" >&2
    exit 1
fi

for f in "${files[@]}"; do
    fname=$(basename "$f" .fasta)
    "$patmat_bin" -sequence "$f" -outfile "$output_dir/${fname}_motifs.tsv" -rformat excel
done
