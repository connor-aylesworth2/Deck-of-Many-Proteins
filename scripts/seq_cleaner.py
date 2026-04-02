#!/usr/bin/python3

# import necessary modules
import sys
from Bio import SeqIO

##### Usage: seq_cleaner.py <input_fasta> <output_fasta> #####



# throw error if expected input and output fasta args are missing
if len(sys.argv) != 3:
    print("Usage: seq_cleaner.py <input_fasta> <output_fasta>")
    sys.exit(1)

# store command-line args as input / output fasta paths
input_fasta = sys.argv[1]
output_fasta = sys.argv[2]

# set for tracking already-seen full sequences so duplicates can be skipped
seqs = set()

# counters for final cleaning summary and debugging
total = 0
kept = 0
skipped_predicted = 0
skipped_contam = 0
skipped_short = 0
skipped_duplicate = 0

# open output fasta for writing cleaned records
with open(output_fasta, "w") as out_handle:
    
    # parse input fasta record by record with biopython
    for record in SeqIO.parse(input_fasta, "fasta"):
        total += 1

        # make lowercase header + uppercase seq for easier checking
        header = record.description.lower()
        seq = str(record.seq).upper()

        # skip predicted proteins
        if "predicted" in header:
            skipped_predicted += 1
            continue

        # skip contamination-labelled entries
        if "contam" in header:
            skipped_contam += 1
            continue

        # skip suspiciously short sequences
        if len(seq) < 100:
            skipped_short += 1
            continue

        # skip exact duplicate full sequence strings
        if seq in seqs:
            skipped_duplicate += 1
            continue
        
        # otherwise keep seq, record it in seen-set, and write to cleaned fasta
        seqs.add(seq)
        SeqIO.write(record, out_handle, "fasta")
        kept += 1

# print final cleaning report for logs
print(f"Total records: {total}")
print(f"Kept: {kept}")
print(f"Skipped predicted: {skipped_predicted}")
print(f"Skipped contam: {skipped_contam}")
print(f"Skipped short: {skipped_short}")
print(f"Skipped duplicate: {skipped_duplicate}")
