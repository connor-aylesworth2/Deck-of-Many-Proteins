#!/usr/bin/python3
import sys
import os
import re
from Bio import SeqIO

if len(sys.argv) != 3:
    print("Usage: seq_separator.py <input_fasta> <output_fasta>")
    sys.exit(1)

input_fasta  = sys.argv[1]
output_dir = sys.argv[2]

os.makedirs(output_dir, exist_ok=True)


for record in SeqIO.parse(input_fasta, "fasta"):
    gud_id = re.sub(r'[^A-Za-z0-9_.-]', '_', record.id)
    out_file = os.path.join(output_dir, f"{gud_id}.fasta")
    SeqIO.write(record, out_file, "fasta")
