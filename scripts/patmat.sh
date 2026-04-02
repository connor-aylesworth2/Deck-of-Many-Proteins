#!/usr/bin/bash

# 
set -euo pipefail

# throw error if expected input + output directory args are missing
if [ "$#" -ne 2 ]; then
    echo "Usage: bash patmat.sh <input_dir> <output_dir>" >&2
    exit 1
fi

# store command-line args as motif input / output directories
input_dir="$1"
output_dir="$2"

# define absolute filepath to patmatmotifs binary
patmat_bin="/usr/bin/patmatmotifs"

# throw error if motif input directory doesn't exist
if [ ! -d "$input_dir" ]; then
    echo "Error: input directory not found: $input_dir" >&2
    exit 1
fi


# make motif output directory if it doesn't already exist
mkdir -p "$output_dir"


# tell bash to return empty list rather than literal *.fasta if no fasta files exist
shopt -s nullglob
files=("$input_dir"/*.fasta)

# throw error if no fasta seqs are found for motif scanning
if [ "${#files[@]}" -eq 0 ]; then
    echo "Error: no FASTA files found in $input_dir" >&2
    exit 1
fi

# loop through each single-sequence fasta and run patmatmotifs on it
for f in "${files[@]}"; do

    # strip .fasta extension for cleaner output filename prefix
    fname=$(basename "$f" .fasta)

    # run patmatmotifs and store tab-delimited excel-format output in output dir
    "$patmat_bin" -sequence "$f" -outfile "$output_dir/${fname}_motifs.tsv" -rformat excel
done
