#!/usr/local/bin/php
<?php
declare(strict_types=1); // declare strict types for easier troubleshooting

/* make sure script is run from command line rather than browser */
if (php_sapi_name() !== 'cli') {fwrite(STDERR, "This script must be run from the command line.\n"); exit(1);}

require __DIR__ . '/../login.php'; // make the pdo connection-containing login.php file mandatory

/* ##### Usage: php populate_sequences.php <job_id> <input_fasta> ##### */



/* ##### Usage: php populate_sequences.php <job_id> <input_fasta> ##### */
if ($argc < 3) {fwrite(STDERR, "Usage: php populate_sequences.php <job_id> <input_fasta>\n"); exit(1);}

/* argc check for required job id + cleaned fasta input */
$job_id = (int)$argv[1];
$input_fasta = $argv[2];

/* throw error if job id is bad */
if ($job_id <= 0) {fwrite(STDERR, "Error: job_id must be a positive integer.\n"); exit(1);}

/* throw error if fasta file doesn't exist */
if (!file_exists($input_fasta)) {fwrite(STDERR, "Error: FASTA file not found: $input_fasta\n"); exit(1);}

/* delete old rows if re-running */
$delete = $pdo->prepare("DELETE FROM sequences WHERE job_id = ?");
$delete->execute([$job_id]);

/* prep insertion statement for cleaned sequence records */
$insert = $pdo->prepare("INSERT INTO sequences (job_id, accession, protein_name, organism_name, sequence_length, fasta_header, sequence_text, source_database) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

/* open fasta file for manual line-by-line parsing */
$handle = fopen($input_fasta, 'r');
if ($handle === false) {fwrite(STDERR, "Error: could not open FASTA file.\n"); exit(1);}

/* make placeholders for parsed fasta records + current working seq */
$records = [];
$current_header = null;
$current_seq = '';

/* loop through fasta file line by line */
while (($line = fgets($handle)) !== false) {$line = trim($line);

    /* ignore blank lines */
    if ($line === '') {continue;}

        /* if line starts with >, we've hit a new fasta header */
    if ($line[0] === '>') {
        /* store previous record before starting next one */
        if ($current_header !== null) {$records[] = [$current_header, $current_seq];}
        $current_header = $line;
        $current_seq = '';
    
    /* otherwise keep building the sequence string */
    } else {$current_seq .= $line;}
}

/* store final fasta record once loop finishes */
if ($current_header !== null) {$records[] = [$current_header, $current_seq];}

fclose($handle); // close fasta file handle once parsing is done
$inserted = 0; // counter for final import report

/* loop through parsed records and import them into sequences table */
foreach ($records as [$header, $sequence]) {
    
    /* skip empty sequences just in case */	
    if ($sequence === '') {continue;}

    /* strip leading > from fasta header for regex parsing */
    $header_text = substr($header, 1);
    
    /* make placeholders for parsed header metadata */
    $accession = null;
    $protein_name = null;
    $organism_name = null;

    /* expected format: accession protein_name [organism] */
    if (preg_match('/^(\S+)\s+(.+)\s+\[(.+)\]$/', $header_text, $matches)) {
        $accession = $matches[1];
        $protein_name = $matches[2];
        $organism_name = $matches[3];
    } else {
        /* keep accession at least */
        $parts = preg_split('/\s+/', $header_text, 2);
        $accession = $parts[0] ?? null;
        $protein_name = $parts[1] ?? null;
        $organism_name = null;
    }

    /* skip record if accession couldn't be recovered */
    if ($accession === null || $accession === '') {continue;}

    /* calculate seq length and insert full record into mysql */
    $sequence_length = strlen($sequence);
    $insert->execute([$job_id, $accession, $protein_name, $organism_name, $sequence_length, $header, $sequence, 'NCBI Protein']);
    $inserted++;
}
/* print final summary for logs */
echo "Inserted {$inserted} sequences for job_id {$job_id}.\n";
