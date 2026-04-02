#!/usr/local/bin/php
<?php
declare(strict_types=1); // declare strict types for easier debugging

/* make sure script is run from command line rather than browser */
if (php_sapi_name() !== 'cli') {fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require __DIR__ . '/../login.php'; // make the pdo connection-containing login.php file mandatory

/* ##### Usage: php motifs_to_sql.php <job_id> <motif_dir> ##### */



/* argc check for required job id + motif results directory */
if ($argc < 3) {fwrite(STDERR, "Usage: php motifs_to_sql.php <job_id> <motif_dir>\n");
    exit(1);
}

/* store passed command-line args */
$jobId = (int)$argv[1];
$motifDir = $argv[2];

/* throw error if job id is bad*/
if ($jobId <= 0) {fwrite(STDERR, "Error: job_id must be a positive integer.\n");
    exit(1);
}

/* throw error if motif results directory doesn't exist */
if (!is_dir($motifDir)) {fwrite(STDERR, "Error: directory not found: $motifDir\n");
    exit(1);
}

/* get all motif tsv files from given output directory */
$files = glob($motifDir . '/*.tsv');
if (!$files) {fwrite(STDERR, "No TSV files found in $motifDir\n");
    exit(1);
}

/* prep statement to match motif hits back to the proper sequence_id via accession + job_id */
$findSequenceStmt = $pdo->prepare("SELECT sequence_id FROM sequences WHERE job_id = :job_id AND accession = :accession LIMIT 1");

/* prep statement to check for duplicate motif hits before inserting */
$checkDuplicateStmt = $pdo->prepare("SELECT motif_hit_id FROM motif_hits WHERE job_id = :job_id AND sequence_id = :sequence_id AND motif_name = :motif_name AND start_pos = :start_pos AND end_pos = :end_pos LIMIT 1");

/* prep insertion statement for new motif hits */
$insertMotifStmt = $pdo->prepare("INSERT INTO motif_hits (job_id, sequence_id, motif_name, prosite_accession, start_pos, end_pos, hit_score, hit_description) VALUES (:job_id, :sequence_id, :motif_name, :prosite_accession, :start_pos, :end_pos, :hit_score, :hit_description)");

/* prep note-updating statement for jobs table */
$updateJobStmt = $pdo->prepare("UPDATE jobs SET notes = :notes WHERE job_id = :job_id");

/* clear old motif rows for reruns so that previous results don't stack up */
$deleteOldStmt = $pdo->prepare("DELETE FROM motif_hits WHERE job_id = :job_id");

/* counters for final import report */
$inserted = 0;
$duplicates = 0;
$skipped = 0;

/* begin import inside try block so whole transaction can be rolled back if needed */
try {
	
        /* wipe previous motif hits for this job before reimporting */
    $pdo->beginTransaction();
    $deleteOldStmt->execute([':job_id' => $jobId]);

        /* loop through each motif tsv file */
    foreach ($files as $filePath) {echo "Processing: $filePath\n";

        /* attempt to open tsv file */
        $handle = fopen($filePath, 'r');
        if ($handle === false) {echo "  Skipped file: could not open\n";
            $skipped++;
            continue;
        }

        /* grab / skip header row; ignore file if empty */
        $header = fgetcsv($handle, 0, "\t");
        if ($header === false) {echo "  Skipped file: empty\n";
            fclose($handle);
            $skipped++;
            continue;
        }

        /* parse file row by row */
	while (($row = fgetcsv($handle, 0, "\t")) !== false) {
            /* skip busted / incomplete rows */
            if (count($row) < 6) {$skipped++; continue;}

	    /* pull key fields out of expected patmatmotifs excel-format tsv */
	    $seqName = trim($row[0]);
            $start   = (int)$row[1];
            $end     = (int)$row[2];
            $score   = is_numeric($row[3]) ? (float)$row[3] : null;
            $motif   = trim($row[5]);

	    /* skip rows missing essential info */
	    if ($seqName === '' || $motif === '' || $start <= 0 || $end <= 0) {$skipped++; continue;}

	    /* find sequence_id for this accession within the correct job */
	    $findSequenceStmt->execute([':job_id' => $jobId, ':accession' => $seqName]);
	    $sequence = $findSequenceStmt->fetch();

	    /* skip row if accession can't be matched back to a stored sequence */
	    if (!$sequence) {echo "  No matching sequence for accession $seqName\n"; $skipped++; continue;}
            $sequenceId = (int)$sequence['sequence_id'];

	    /* check whether this exact motif hit has already been stored */
	    $checkDuplicateStmt->execute([':job_id' => $jobId, ':sequence_id' => $sequenceId, ':motif_name' => $motif, ':start_pos' => $start, ':end_pos' => $end]);

	    /* skip duplicate rows rather than double-inserting */
	    if ($checkDuplicateStmt->fetch()) {$duplicates++; continue;}

	    /* insert motif hit into mysql table */
	    $insertMotifStmt->execute([':job_id' => $jobId, ':sequence_id' => $sequenceId, ':motif_name' => $motif, ':prosite_accession' => null, ':start_pos' => $start, ':end_pos' => $end, ':hit_score' => $score, ':hit_description' => null]);
            $inserted++;
	}
	/* close file handle once current tsv is fully parsed */
        fclose($handle);
    }

    /* build job notes summary and store it in jobs table */
    $note = "Motif import complete: $inserted inserted, $duplicates duplicates skipped, $skipped skipped.";
    $updateJobStmt->execute([':notes' => $note, ':job_id' => $jobId]);

    /* commit transaction once all files / rows are handled */
    $pdo->commit();
    
    /* print import summary for logs */
    echo "Import complete.\n";
    echo "Inserted: $inserted\n";
    echo "Duplicates skipped: $duplicates\n";
    echo "Other skipped rows/files: $skipped\n";

/* rollback transaction if anything nasty happens */
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {$pdo->rollBack();}
    fwrite(STDERR, "Import failed: " . $e->getMessage() . "\n");
    exit(1);
}
