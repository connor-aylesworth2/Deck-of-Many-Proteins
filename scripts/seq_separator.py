#!/usr/bin/python3

# import necessary modules
import sys
import os
import re
from Bio import SeqIO

##### Usage: seq_separator.py <input_fasta> <output_dir> #####



# throw error if expected input fasta + output directory args are missing
if len(sys.argv) != 3:
    print("Usage: seq_separator.py <input_fasta> <output_fasta>")
    sys.exit(1)

# store command-line args as multi-fasta input + per-seq output dir
input_fasta  = sys.argv[1]
output_dir = sys.argv[2]

# make output directory if it doesn't already exist
os.makedirs(output_dir, exist_ok=True)

# parse multi-fasta and split each record into its own single-sequence fasta file
for record in SeqIO.parse(input_fasta, "fasta"):

    # make filenames filesystem-friendly
    gud_id = re.sub(r'[^A-Za-z0-9_.-]', '_', record.id)
    
    # build output filepath for this single record
    out_file = os.path.join(output_dir, f"{gud_id}.fasta")
    
    # write one fasta file per sequence
    SeqIO.write(record, out_file, "fasta")
