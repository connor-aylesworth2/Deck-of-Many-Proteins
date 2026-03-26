#!/usr/bin/python3

from Bio import SeqIO

input_fasta = "g6_aves.fasta"
output_fasta = "g6_aves_cleaned.fasta"

seqs = set()

with open(output_fasta, "w") as out_handle:
    for record in SeqIO.parse(input_fasta, "fasta"):
        header = record.description.lower()
        seq = str(record.seq).upper()

        if "predicted" in header or "contam" in header:
            continue

        if len(seq) < 100:
            continue

        if seq in seqs:
            continue
        
        seqs.add(seq)
        SeqIO.write(record, out_handle, "fasta")
