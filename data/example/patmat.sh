#!/usr/bin/bash

for f in motifs/*.fasta
do
	base=$(basename "$f" .fasta)
	patmatmotifs -sequence "$f" -outfile "motif_outputs/${base}_motifs.txt"
done
