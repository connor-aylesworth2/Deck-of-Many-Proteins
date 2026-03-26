#!/usr/bin/python3

from Bio import SeqIO
import os
import re

input_fasta  = "g6_aves_cleaned.fasta"

for i, record in enumerate(SeqIO.parse(input_fasta, "fasta"), start=1):
    gud_id = re.sub(r'[^A-Za-z0-9_.-]', '_', record.id)
    out_file = os.path.join("motifs", f"{gud_id}.fasta")
    SeqIO.write(record, out_file, "fasta")
