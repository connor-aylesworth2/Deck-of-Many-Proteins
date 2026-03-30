#!/usr/local/bin/php
<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    exit("This script must be run from the command line.\n");
}

require __DIR__ . '/login.php';

if ($argc < 2) {
    fwrite(STDERR, "Usage: php run_job.php <job_id>\n");
    exit(1);
}

$job_id = (int)$argv[1];
if ($job_id <= 0) {
    fwrite(STDERR, "Error: invalid job_id\n");
    exit(1);
}

$php_bin = '/usr/local/bin/php';
$python_bin = '/usr/bin/python3';
$bash_bin = '/usr/bin/bash';

$esearch_bin = '/home/s2837739/edirect/esearch';
$efetch_bin = '/home/s2837739/edirect/efetch';
$clustalo_bin = '/usr/bin/clustalo';
$plotcon_bin = '/usr/bin/plotcon';

$clear_files = $pdo->prepare("DELETE FROM job_files WHERE job_id = ?");
$clear_files->execute([$job_id]);

function updateJob(PDO $pdo, int $job_id, array $fields): void {
    $sets = [];
    $params = [':job_id' => $job_id];

    foreach ($fields as $col => $val) {
        $sets[] = "$col = :$col";
        $params[":$col"] = $val;
    }

    $sql = "UPDATE jobs SET " . implode(', ', $sets) . " WHERE job_id = :job_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function addJobFile(PDO $pdo, int $job_id, string $file_type, string $file_path, ?string $description = null): void {
    $stmt = $pdo->prepare("
        INSERT INTO job_files (job_id, file_type, file_path, description)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$job_id, $file_type, $file_path, $description]);
}

function runCommand(string $cmd): void {
    $output = [];
    exec($cmd . ' 2>&1', $output, $exit_code);
    if (!empty($output)) {
        echo implode("\n", $output) . "\n";
    }
    if ($exit_code !== 0) {
        throw new RuntimeException(
            "Command failed: $cmd\n" . implode("\n", $output)
        );
    }
}

try {
    updateJob($pdo, $job_id, [
        'status' => 'running',
        'error_message' => null,
        'notes' => 'Pipeline started'
    ]);

    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE job_id = ?");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();

    if (!$job) {
        throw new RuntimeException("Job not found: $job_id");
    }

    $protein_family = $job['protein_family'];
    $taxon_group = $job['taxon_group'];
    $search_query = $job['search_query'];

    $job_dir = __DIR__ . "/data/jobs/job_" . $job_id;
    $input_dir = $job_dir . "/patmat_input";
    $output_dir = $job_dir . "/patmat_outputs";

    if (!is_dir($job_dir)) mkdir($job_dir, 0755, true);
    if (!is_dir($input_dir)) mkdir($input_dir, 0755, true);
    if (!is_dir($output_dir)) mkdir($output_dir, 0755, true);

    $raw_fasta = $job_dir . "/raw.fasta";
    $clean_fasta = $job_dir . "/cleaned.fasta";
    $msa_fasta = $job_dir . "/alignment.fasta";
    $plot_prefix = $job_dir . "/plotcon";
    $plot_png = $job_dir . "/plotcon.1.png";

    updateJob($pdo, $job_id, ['notes' => 'Fetching sequences from NCBI']);

    $fetch_cmd = escapeshellarg($esearch_bin) . " -db protein -query " .
        escapeshellarg($search_query) .
        " | " .
        escapeshellarg($efetch_bin) . " -format fasta > " .
        escapeshellarg($raw_fasta);
        runCommand($fetch_cmd);

    if (!file_exists($raw_fasta) || filesize($raw_fasta) === 0) {
        throw new RuntimeException("No FASTA data retrieved.");
    }

    addJobFile($pdo, $job_id, 'raw_fasta', "data/jobs/job_{$job_id}/raw.fasta", 'Raw fetched FASTA');

    updateJob($pdo, $job_id, ['notes' => 'Cleaning FASTA sequences']);

    $clean_cmd = escapeshellarg($python_bin) . " " .
    escapeshellarg(__DIR__ . "/scripts/seq_cleaner.py") . " " .
    escapeshellarg($raw_fasta) . " " .
    escapeshellarg($clean_fasta);

    echo "Running cleaner command:\n$clean_cmd\n";
runCommand($clean_cmd);

echo "After cleaner:\n";
echo "clean_fasta path: $clean_fasta\n";
echo "exists: " . (file_exists($clean_fasta) ? "yes" : "no") . "\n";
if (file_exists($clean_fasta)) {
    echo "size: " . filesize($clean_fasta) . "\n";
}

if (!file_exists($clean_fasta) || filesize($clean_fasta) === 0) {
    throw new RuntimeException("No cleaned FASTA produced.");
}
    addJobFile($pdo, $job_id, 'clean_fasta', "data/jobs/job_{$job_id}/cleaned.fasta", 'Cleaned FASTA');

    updateJob($pdo, $job_id, ['notes' => 'Importing cleaned sequences into database']);
    
    $import_seq_cmd = escapeshellarg($php_bin) . " " .
    escapeshellarg(__DIR__ . "/scripts/populate_sequences.php") . " " .
    escapeshellarg((string)$job_id) . " " .
    escapeshellarg($clean_fasta);
    echo "Running cleaner command:\n$clean_cmd\n";
    runCommand($import_seq_cmd);
    echo "After cleaner:\n";
echo "clean_fasta path: $clean_fasta\n";
echo "exists: " . (file_exists($clean_fasta) ? "yes" : "no") . "\n";
if (file_exists($clean_fasta)) {
    echo "size: " . filesize($clean_fasta) . "\n";
}
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM sequences WHERE job_id = ?");
    $count_stmt->execute([$job_id]);
    $total_seqs = (int)$count_stmt->fetchColumn();

    if ($total_seqs === 0) {
        throw new RuntimeException("No sequences were imported.");
    }

    updateJob($pdo, $job_id, [
        'total_seqs' => $total_seqs,
        'notes' => 'Running Clustal Omega'
    ]);

    $clustalo_cmd = escapeshellarg($clustalo_bin) . " -i " .
    escapeshellarg($clean_fasta) .
    " -o " . escapeshellarg($msa_fasta) . " --force";
    runCommand($clustalo_cmd);

    addJobFile($pdo, $job_id, 'msa_fasta', "data/jobs/job_{$job_id}/alignment.fasta", 'Clustal Omega alignment');

    updateJob($pdo, $job_id, ['notes' => 'Generating conservation plot']);

    $plotcon_cmd = "printf '4\n' | " . escapeshellarg($plotcon_bin) .
    " -sequences " . escapeshellarg($msa_fasta) .
    " -graph png -goutfile " . escapeshellarg($plot_prefix);
    runCommand($plotcon_cmd);

    if (file_exists($plot_png)) {
        addJobFile($pdo, $job_id, 'plotcon_png', "data/jobs/job_{$job_id}/plotcon.1.png", 'Conservation plot');
    }

    updateJob($pdo, $job_id, ['notes' => 'Preparing motif input FASTA files']);

    $split_cmd = escapeshellarg($python_bin) . " " .
    escapeshellarg(__DIR__ . "/scripts/seq_separator.py") . " " .
    escapeshellarg($clean_fasta) . " " .
    escapeshellarg($input_dir);
    runCommand($split_cmd);

    updateJob($pdo, $job_id, ['notes' => 'Running patmatmotifs']);

    $motif_cmd = escapeshellarg($bash_bin) . " " .
    escapeshellarg(__DIR__ . "/scripts/patmat.sh") . " " .
    escapeshellarg($input_dir) . " " .
    escapeshellarg($output_dir);
    runCommand($motif_cmd);

    updateJob($pdo, $job_id, ['notes' => 'Importing motif hits']);

    $import_motif_cmd = escapeshellarg($php_bin) . " " .
    escapeshellarg(__DIR__ . "/scripts/motifs_to_sql.php") . " " .
    escapeshellarg((string)$job_id) . " " .
    escapeshellarg($output_dir);
    runCommand($import_motif_cmd);

    addJobFile($pdo, $job_id, 'motif_dir', "data/jobs/job_{$job_id}/patmat_outputs", 'patmatmotifs outputs');

    updateJob($pdo, $job_id, [
        'status' => 'complete',
        'done_time' => date('Y-m-d H:i:s'),
        'notes' => "Pipeline complete: {$total_seqs} sequences processed."
    ]);

    echo "Job {$job_id} completed successfully.\n";

} catch (Throwable $e) {
    updateJob($pdo, $job_id, [
        'status' => 'failed',
        'done_time' => date('Y-m-d H:i:s'),
        'error_message' => $e->getMessage(),
        'notes' => 'Pipeline failed'
    ]);
    fwrite(STDERR, "Job failed: " . $e->getMessage() . "\n");
    exit(1);
}
