<?php
require 'login.php'; // require pdo connection-containing login.php file
include 'header.php'; // include header section on history page

/* prep jobs pdo request and execute to fetch all job data ever recorded in jobs table */
$stmt = $pdo->prepare("SELECT job_id, protein_family, taxon_group, status, total_seqs, send_time, done_time, is_example, notes, error_message FROM jobs ORDER BY send_time DESC");
$stmt->execute();
$jobs = $stmt->fetchAll();
?>



<!-- Display Previous Job Data in large Table -->
<main class="container">
<section class="card">
<h2>Previous Jobs</h2>
<p>This page lists previously submitted analyses, including the example dataset and user-submitted jobs. Click “View Job” to open the full results page.</p>

<!-- conditionally display previous jobs if any exist -->
<?php if (empty($jobs)): ?>
<p class="info">No previous jobs were found.</p>
<?php else: ?>
<table>
<tr>
<th>Job ID</th>
<th>Protein Family</th>
<th>Taxon Group</th>
<th>Status</th>
<th>Sequences</th>
<th>Submitted</th>
<th>Finished</th>
<th>Type</th>
<th>View</th>
</tr>

<?php foreach ($jobs as $job): ?>
    <tr>
    <td><?php echo htmlspecialchars((string)$job['job_id']); ?></td>
    <td><?php echo htmlspecialchars($job['protein_family']); ?></td>
    <td><?php echo htmlspecialchars($job['taxon_group']); ?></td>

    <!-- assign css status class so complete / failed / queued jobs are easier to visually scan -->
    <td><?php $status = $job['status']; $class = 'info'; if ($status === 'complete') {
        $class = 'success';
    } elseif ($status === 'failed') {
        $class = 'error';
    } elseif ($status === 'queued' || $status === 'running') {
        $class = 'warning';
    }?>
    <span class="<?php echo $class; ?>">
    <?php echo htmlspecialchars($status); ?>
    </span>
    </td>
    <td><?php echo htmlspecialchars((string)$job['total_seqs']); ?></td>
    <td><?php echo htmlspecialchars($job['send_time']); ?></td>
    <td><?php echo $job['done_time'] !== null ? htmlspecialchars($job['done_time']) : '—'; ?></td>
    <td><?php echo $job['is_example'] ? 'Example' : 'User'; ?></td>

    <!-- link each history row to its dedicated results page -->
    <td><a href="job.php?job_id=<?php echo urlencode((string)$job['job_id']); ?>">View Job</a></td>
    </tr>

    <!-- if a job failed, print its stored error directly below the main row -->
    <?php if ($job['status'] === 'failed' && !empty($job['error_message'])): ?>
    <tr>
    <td colspan="9">
    <strong>Error:</strong>
    <?php echo htmlspecialchars($job['error_message']); ?>
    </td>
    </tr>
    <?php endif; ?>

    <!-- print pipeline notes / completion notes below each job when available -->
    <?php if (!empty($job['notes'])): ?>
    <tr>
    <td colspan="9">
    <strong>Notes:</strong>
    <?php echo htmlspecialchars($job['notes']); ?>
    </td>
    </tr>
    <?php endif; ?>

<?php endforeach; ?>
</table>
<?php endif; ?>
</section>
</main>

<?php include 'footer.php'; ?> <!-- include footer section on history page -->
