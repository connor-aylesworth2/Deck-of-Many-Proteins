#!/usr/local/bin/php
<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {fwrite(STDERR, "This script must be run from the command line.\n"); exit(1);}

require __DIR__ . '/../login.php';

if ($argc < 3) {fwrite(STDERR, "Usage: php populate_sequences.php <job_id> <input_fasta>\n"); exit(1);}

$job_id = (int)$argv[1];
$input_fasta = $argv[2];

if ($job_id <= 0) {fwrite(STDERR, "Error: job_id must be a positive integer.\n"); exit(1);}

if (!file_exists($input_fasta)) {fwrite(STDERR, "Error: FASTA file not found: $input_fasta\n"); exit(1);}

/* delete old rows if re-running */
$delete = $pdo->prepare("DELETE FROM sequences WHERE job_id = ?");
$delete->execute([$job_id]);

$insert = $pdo->prepare("INSERT INTO sequences (job_id, accession, protein_name, organism_name, sequence_length, fasta_header, sequence_text, source_database) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

$handle = fopen($input_fasta, 'r');
if ($handle === false) {fwrite(STDERR, "Error: could not open FASTA file.\n"); exit(1);}

$records = [];
$current_header = null;
$current_seq = '';

while (($line = fgets($handle)) !== false) {$line = trim($line);
    if ($line === '') {continue;}

    if ($line[0] === '>') {
        if ($current_header !== null) {$records[] = [$current_header, $current_seq];}
        $current_header = $line;
        $current_seq = '';
    } else {$current_seq .= $line;}
}

if ($current_header !== null) {$records[] = [$current_header, $current_seq];}

fclose($handle);

$inserted = 0;

foreach ($records as [$header, $sequence]) {
    if ($sequence === '') {continue;}

    $header_text = substr($header, 1);
    $accession = null;
    $protein_name = null;
    $organism_name = null;

    if (preg_match('/^(\S+)\s+(.+)\s+\[(.+)\]$/', $header_text, $matches)) {
        $accession = $matches[1];
        $protein_name = $matches[2];
        $organism_name = $matches[3];
    } else {
        /* fallback: keep accession at least */
        $parts = preg_split('/\s+/', $header_text, 2);
        $accession = $parts[0] ?? null;
        $protein_name = $parts[1] ?? null;
        $organism_name = null;
    }

    if ($accession === null || $accession === '') {continue;}

    $sequence_length = strlen($sequence);
    $insert->execute([$job_id, $accession, $protein_name, $organism_name, $sequence_length, $header, $sequence, 'NCBI Protein']);
    $inserted++;
}

echo "Inserted {$inserted} sequences for job_id {$job_id}.\n";
