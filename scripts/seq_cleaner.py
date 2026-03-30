#!/usr/bin/python3
import sys
from Bio import SeqIO

if len(sys.argv) != 3:
    print("Usage: seq_cleaner.py <input_fasta> <output_fasta>")
    sys.exit(1)

input_fasta = sys.argv[1]
output_fasta = sys.argv[2]

seqs = set()

total = 0
kept = 0
skipped_predicted = 0
skipped_contam = 0
skipped_short = 0
skipped_duplicate = 0

with open(output_fasta, "w") as out_handle:
    for record in SeqIO.parse(input_fasta, "fasta"):
        total += 1
        header = record.description.lower()
        seq = str(record.seq).upper()

        if "predicted" in header:
            skipped_predicted += 1
            continue

        if "contam" in header:
            skipped_contam += 1
            continue

        if len(seq) < 100:
            skipped_short += 1
            continue

        if seq in seqs:
            skipped_duplicate += 1
            continue

        seqs.add(seq)
        SeqIO.write(record, out_handle, "fasta")
        kept += 1

print(f"Total records: {total}")
print(f"Kept: {kept}")
print(f"Skipped predicted: {skipped_predicted}")
print(f"Skipped contam: {skipped_contam}")
print(f"Skipped short: {skipped_short}")
print(f"Skipped duplicate: {skipped_duplicate}")
