<?php
require 'login.php';

$error = '';
$protein_family = '';
$taxon_group = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $protein_family = trim($_POST['protein_family'] ?? '');
    $taxon_group = trim($_POST['taxon_group'] ?? '');

    if ($protein_family === '' || $taxon_group === '') {
        $error = 'Please provide both a protein family and a taxonomic group.';
    } elseif (strlen($protein_family) > 255 || strlen($taxon_group) > 255) {
        $error = 'Input is too long.';
    } else {
        $search_query = "{$protein_family}[Protein Name] AND {$taxon_group}[Organism]";

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO jobs (
                    protein_family,
                    taxon_group,
                    search_query,
                    status,
                    is_example
                ) VALUES (?, ?, ?, 'queued', 0)
            ");
            $stmt->execute([$protein_family, $taxon_group, $search_query]);

            $job_id = (int)$pdo->lastInsertId();
            $pdo->commit();

            $job_dir = __DIR__ . "/data/jobs/job_" . $job_id;
            if (!is_dir($job_dir) && !mkdir($job_dir, 0755, true)) {
                throw new Exception("Failed to create job directory: $job_dir");
            }

            $log_dir = __DIR__ . "/logs";
            if (!is_dir($log_dir) && !mkdir($log_dir, 0755, true)) {
                throw new Exception("Failed to create log directory: $log_dir");
            }

            $php_bin = '/usr/local/bin/php';
            $run_script = __DIR__ . '/run_job.php';
            $log_file = $log_dir . "/job_" . $job_id . ".log";

            $cmd = sprintf(
                '%s %s %d > %s 2>&1 &',
                escapeshellarg($php_bin),
                escapeshellarg($run_script),
                $job_id,
                escapeshellarg($log_file)
            );

	    exec($cmd, $output, $return_var);
	    $error = "CMD: $cmd | return=$return_var | output=" . implode("\n", $output);


            header("Location: job.php?job_id=" . $job_id);
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Failed to create job: ' . $e->getMessage();
        }
    }
}

include 'header.php';
?>

<main class="container">
    <section class="card">
        <h2>Run a New Protein Analysis</h2>
        <p>
            Submit a protein family and taxonomic group to fetch sequences,
            analyse conservation, scan for PROSITE motifs, and store the results.
        </p>

        <?php if ($error !== ''): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post" action="submit.php">
            <label for="protein_family">Protein family</label>
            <input
                type="text"
                id="protein_family"
                name="protein_family"
                value="<?php echo htmlspecialchars($protein_family); ?>"
                placeholder="e.g. glucose-6-phosphatase"
                required
            >

            <label for="taxon_group">Taxonomic group</label>
            <input
                type="text"
                id="taxon_group"
                name="taxon_group"
                value="<?php echo htmlspecialchars($taxon_group); ?>"
                placeholder="e.g. Aves"
                required
            >

            <input type="submit" value="Submit Analysis">
        </form>
    </section>
</main>

<?php include 'footer.php'; ?>
