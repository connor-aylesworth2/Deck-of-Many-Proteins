#!/usr/local/bin/php
<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require __DIR__ . '/../login.php';

/* ##### Usage: php motifs_to_sql.php <job_id> <motif_dir> ##### */

if ($argc < 3) {fwrite(STDERR, "Usage: php motifs_to_sql.php <job_id> <motif_dir>\n");
    exit(1);
}

$jobId = (int)$argv[1];
$motifDir = $argv[2];

if ($jobId <= 0) {fwrite(STDERR, "Error: job_id must be a positive integer.\n");
    exit(1);
}

if (!is_dir($motifDir)) {fwrite(STDERR, "Error: directory not found: $motifDir\n");
    exit(1);
}

$files = glob($motifDir . '/*.tsv');
if (!$files) {fwrite(STDERR, "No TSV files found in $motifDir\n");
    exit(1);
}

$findSequenceStmt = $pdo->prepare("SELECT sequence_id FROM sequences WHERE job_id = :job_id AND accession = :accession LIMIT 1");

$checkDuplicateStmt = $pdo->prepare("SELECT motif_hit_id FROM motif_hits WHERE job_id = :job_id AND sequence_id = :sequence_id AND motif_name = :motif_name AND start_pos = :start_pos AND end_pos = :end_pos LIMIT 1");

$insertMotifStmt = $pdo->prepare("INSERT INTO motif_hits (job_id, sequence_id, motif_name, prosite_accession, start_pos, end_pos, hit_score, hit_description) VALUES (:job_id, :sequence_id, :motif_name, :prosite_accession, :start_pos, :end_pos, :hit_score, :hit_description)");

$updateJobStmt = $pdo->prepare("UPDATE jobs SET notes = :notes WHERE job_id = :job_id");

/* optional: clear old motif rows for reruns */
$deleteOldStmt = $pdo->prepare("DELETE FROM motif_hits WHERE job_id = :job_id");

$inserted = 0;
$duplicates = 0;
$skipped = 0;

try {
    $pdo->beginTransaction();
    $deleteOldStmt->execute([':job_id' => $jobId]);

    foreach ($files as $filePath) {echo "Processing: $filePath\n";
        $handle = fopen($filePath, 'r');
        if ($handle === false) {echo "  Skipped file: could not open\n";
            $skipped++;
            continue;
        }

        $header = fgetcsv($handle, 0, "\t");
        if ($header === false) {echo "  Skipped file: empty\n";
            fclose($handle);
            $skipped++;
            continue;
        }

        while (($row = fgetcsv($handle, 0, "\t")) !== false) {
            if (count($row) < 6) {$skipped++; continue;}

            $seqName = trim($row[0]);
            $start   = (int)$row[1];
            $end     = (int)$row[2];
            $score   = is_numeric($row[3]) ? (float)$row[3] : null;
            $motif   = trim($row[5]);

            if ($seqName === '' || $motif === '' || $start <= 0 || $end <= 0) {$skipped++; continue;}

            $findSequenceStmt->execute([':job_id' => $jobId, ':accession' => $seqName]);

            $sequence = $findSequenceStmt->fetch();

            if (!$sequence) {echo "  No matching sequence for accession $seqName\n"; $skipped++; continue;}
            $sequenceId = (int)$sequence['sequence_id'];

            $checkDuplicateStmt->execute([':job_id' => $jobId, ':sequence_id' => $sequenceId, ':motif_name' => $motif, ':start_pos' => $start, ':end_pos' => $end]);

            if ($checkDuplicateStmt->fetch()) {$duplicates++; continue;}

            $insertMotifStmt->execute([':job_id' => $jobId, ':sequence_id' => $sequenceId, ':motif_name' => $motif, ':prosite_accession' => null, ':start_pos' => $start, ':end_pos' => $end, ':hit_score' => $score, ':hit_description' => null]);
            $inserted++;
        }
        fclose($handle);
    }

    $note = "Motif import complete: $inserted inserted, $duplicates duplicates skipped, $skipped skipped.";
    $updateJobStmt->execute([':notes' => $note, ':job_id' => $jobId]);

    $pdo->commit();
    echo "Import complete.\n";
    echo "Inserted: $inserted\n";
    echo "Duplicates skipped: $duplicates\n";
    echo "Other skipped rows/files: $skipped\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {$pdo->rollBack();}
    fwrite(STDERR, "Import failed: " . $e->getMessage() . "\n");
    exit(1);
}
