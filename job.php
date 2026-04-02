<?php 
require 'login.php'; // make pdo connection-containing file mandatory
include 'header.php'; // include the header on each job results page

/* read in job ID with HTTP GET from the URL */
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

/* throws error if job id is negative */
if ($job_id <= 0) {echo '<main class="container"><p class="error">Invalid job ID.</p></main>';
    include 'footer.php';
    exit;
}

/* fetch main job record with pdo */
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE job_id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

/* other invalid job id checker */
if (!$job) {echo '<main class="container"><p class="error">Job not found.</p></main>';
    include 'footer.php';
    exit;
}

/* fetch all possible file records for current job from job_files table with pdo */
$file_stmt = $pdo->prepare("SELECT file_type, file_path, description, created_at FROM job_files WHERE job_id = ? ORDER BY created_at ASC");
$file_stmt->execute([$job_id]);
$files = $file_stmt->fetchAll();

/* fetch all possible sequences for current job from sequences table with pdo */
$seq_stmt = $pdo->prepare("SELECT accession, protein_name, organism_name, sequence_length FROM sequences WHERE job_id = ? ORDER BY organism_name, accession");
$seq_stmt->execute([$job_id]);
$sequences = $seq_stmt->fetchAll();

/* fetch all possible motif hits for current job from motif_hits and sequences tables wit pdo */
$motif_stmt = $pdo->prepare("SELECT s.accession, s.organism_name, mh.motif_name, mh.prosite_accession, mh.start_pos, mh.end_pos, mh.hit_score FROM motif_hits mh JOIN sequences s ON mh.sequence_id = s.sequence_id WHERE mh.job_id = ? ORDER BY s.organism_name, s.accession, mh.start_pos");
$motif_stmt->execute([$job_id]);
$motifs = $motif_stmt->fetchAll();

/* find filepath for corresponding plotcon output */
$plot_path = null;
foreach ($files as $file) {
    if ($file['file_type'] === 'plotcon_png') {$plot_path = $file['file_path'];
        break;
    }
}
?>



<!-- ##### Actual Job Results HTML Display ##### -->

<!-- define main page and first card for job search_query description -->
<main class="container">
<section class="card">
<h2>Job <?php echo htmlspecialchars((string)$job['job_id']); ?></h2>
<p><strong>Protein family:</strong> <?php echo htmlspecialchars($job['protein_family']); ?></p>
<p><strong>Taxonomic group:</strong> <?php echo htmlspecialchars($job['taxon_group']); ?></p>
<p><strong>Search query:</strong> <?php echo htmlspecialchars($job['search_query']); ?></p>
<p><strong>Status:</strong> <?php echo htmlspecialchars($job['status']); ?></p>
<p><strong>Sequences imported:</strong> <?php echo htmlspecialchars((string)$job['total_seqs']); ?></p>
<p><strong>Submitted:</strong> <?php echo htmlspecialchars($job['send_time']); ?></p>

<!-- conditionally show finish time so displays only when finish time exists -->
<?php if ($job['done_time'] !== null): ?>
<p><strong>Finished:</strong> <?php echo htmlspecialchars($job['done_time']); ?></p>
<?php endif; ?>

<!-- conditionally show notes so displays only when notes exist -->
<?php if ($job['notes'] !== null): ?>
<p><strong>Notes:</strong> <?php echo htmlspecialchars($job['notes']); ?></p>
<?php endif; ?>

<!-- displays various status messages depending on status field of job in jobs mysql table -->
<?php if ($job['status'] === 'failed' && $job['error_message'] !== null): ?>
<p class="error"><strong>Error:</strong> <?php echo htmlspecialchars($job['error_message']); ?></p>
<?php elseif ($job['status'] === 'running' || $job['status'] === 'queued'): ?>
<p class="info">This job is still being processed. Refresh this page in a short while.</p>
<?php elseif ($job['status'] === 'complete'): ?>
<p class="success">This analysis completed successfully.</p>
<?php endif; ?>
</section>

<!-- conditionally display output files depending on whether output files exist -->
<?php if (!empty($files)): ?>
<section class="card">
<h3>Output Files</h3>
<ul>
<?php foreach ($files as $file): ?>
    <?php if (is_dir($file['file_path'])) continue; ?>
    <li>
    <a href="<?php echo htmlspecialchars($file['file_path']); ?>">
    <?php echo htmlspecialchars($file['description'] ?? $file['file_type']); ?>
    </a>
    (<?php echo htmlspecialchars($file['file_type']); ?>)
    </li>
<?php endforeach; ?>
</ul>
</section>
<?php endif; ?>

<!-- conditionally display plotcon png if png exists -->
<?php if ($plot_path !== null && file_exists(__DIR__ . '/' . $plot_path)): ?>
<section class="card">
<h3>Conservation Plot</h3>
<img
    class="plot"
    src="<?php echo htmlspecialchars($plot_path); ?>"
    alt="Conservation plot for job <?php echo htmlspecialchars((string)$job_id); ?>" >
</section>
<?php endif; ?>

<!-- conditionally display sequences from sequences table if sequences exist -->
<?php if (!empty($sequences)): ?>
<section class="card">
<h3>Sequences</h3>
<table> <!-- define sequence table -->
<tr>
<th>Accession</th>
<th>Protein Name</th>
<th>Organism</th>
<th>Length (aa)</th>
</tr>
<?php foreach ($sequences as $row): ?>
    <tr>
    <td><?php echo htmlspecialchars($row['accession']); ?></td>
    <td><?php echo htmlspecialchars($row['protein_name'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($row['organism_name'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars((string)($row['sequence_length'] ?? '')); ?></td>
    </tr>
<?php endforeach; ?>
</table>
</section>
<?php endif; ?>

<!-- conditionally display motif hits from motif_hits table if motif hits exist -->
<?php if (!empty($motifs)): ?>
<section class="card">
<h3>Motif Hits</h3>
<table> <!-- define motif hits table -->
<tr>
<th>Accession</th>
<th>Organism</th>
<th>Motif</th>
<th>PROSITE</th>
<th>Start</th>
<th>End</th>
<th>Score</th>
</tr>
<?php foreach ($motifs as $row): ?>
    <tr>
    <td><?php echo htmlspecialchars($row['accession']); ?></td>
    <td><?php echo htmlspecialchars($row['organism_name']); ?></td>
    <td><?php echo htmlspecialchars($row['motif_name']); ?></td>
    <td><?php echo htmlspecialchars($row['prosite_accession'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars((string)$row['start_pos']); ?></td>
    <td><?php echo htmlspecialchars((string)$row['end_pos']); ?></td>
    <td><?php echo $row['hit_score'] !== null ? htmlspecialchars((string)$row['hit_score']) : 'N/A'; ?></td>
    </tr>
<?php endforeach; ?>
</table>
</section>
<?php endif; ?>
</main>

<?php include 'footer.php'; ?> <!-- include footer -->
