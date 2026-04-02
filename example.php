<?php
include 'header.php';
require 'login.php'; 

$example_dir = "data/example";
$motif_dir = $example_dir . "/patmat_outputs";

$raw_fasta = $example_dir . "/g6_aves.fasta";
$clean_fasta = $example_dir . "/g6_aves_cleaned.fasta";
$msa_fasta = $example_dir . "/g6_aves_MSA.fasta";
$plotcon_png = $example_dir . "/g6_aves_plotcon.1.png";

$total_seqs = 0;
if (file_exists($clean_fasta)) {
    $lines = file($clean_fasta);
    foreach ($lines as $line) {
        if (strpos($line, '>') === 0) {
            $total_seqs++;
        }
    }
}

$motif_files = [];
if (is_dir($motif_dir)) {
    $motif_files = glob($motif_dir . "/*");
    sort($motif_files);
}


$motif_rows = [];



/* get example job input seqs */
$job_stmt = $pdo->prepare("
    SELECT job_id
    FROM jobs
    WHERE is_example = 1
    ");
$job_stmt->execute();
$example_job = $job_stmt->fetch();

$sequence_rows = [];

if ($example_job) {
    $job_id = $example_job['job_id'];

    $seq_stmt = $pdo->prepare("
        SELECT accession, protein_name, organism_name, sequence_length
        FROM sequences
        WHERE job_id = ?
        ORDER BY organism_name, accession
    ");
    $seq_stmt->execute([$job_id]);
    $sequence_rows = $seq_stmt->fetchAll();
}

if ($example_job) {
    $job_id = $example_job['job_id'];

    $motif_stmt = $pdo->prepare("
        SELECT
            s.accession,
            s.organism_name,
            mh.motif_name,
            mh.start_pos,
            mh.end_pos,
            mh.hit_score,
            mh.hit_description
        FROM motif_hits mh
        JOIN sequences s
            ON mh.sequence_id = s.sequence_id
        WHERE mh.job_id = ?
        ORDER BY s.organism_name, s.accession, mh.start_pos
    ");
    $motif_stmt->execute([$job_id]);
    $motif_rows = $motif_stmt->fetchAll();
}
?>



<main class="container">
<h2>Example Dataset: Glucose-6-Phosphatase Proteins from Aves</h2>
<p>This page demonstrates the full workflow that this webpage executes using a precomputed example dataset of glucose-6-phosphatase protein sequences retrieved from birds (Aves). The sequences were filtered for quality and duplicates, aligned against each other with Clustal Omega, analysed for conservation with EMBOSS plotcon, scanned for motifs using EMBOSS patmatmotifs, and another thing.</p>



<section class="card">
<h3>Precomputed Dataset Summary</h3>
<p><strong>Protein family:</strong> Glucose-6-phosphatase</p>
<p><strong>Taxon group:</strong> Aves</p>
<p><strong>Number of cleaned sequences:</strong> <?php echo $total_seqs; ?></p>
</section>



<section class="card">
<h3>Example Sequences</h3>
<p>The table below contains a filtered list of glucose-6-phosphatase protein sequences retrieved from NCBI's Protein DB for the avian example dataset. Sequences were attained with the following unix command: <code>esearch -db protein -query '"glucose-6-phosphatase"[Protein Name] AND Aves[Organism]' | efetch -format fasta &gt; g6_aves.fasta</code> </p>

<?php if (!empty($sequence_rows)): ?>
<table>
<tr>
<th>Accession</th>
<th>Protein Name</th>
<th>Organism</th>
<th>Length (aa)</th>
</tr>

<?php foreach ($sequence_rows as $row): ?>
<tr>
<td><?php echo htmlspecialchars($row['accession']); ?></td>
<td><?php echo htmlspecialchars($row['protein_name']); ?></td>
<td><?php echo htmlspecialchars($row['organism_name']); ?></td>
<td><?php echo htmlspecialchars($row['sequence_length']); ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p>No sequences were found for the example dataset.</p>
<?php endif; ?>
</section>



<section class="card">
<h3>Download Files</h3>
<ul>
<?php if (file_exists($raw_fasta)): ?>
<li><a href="<?php echo $raw_fasta; ?>">Raw FASTA sequences</a></li>
<?php endif; ?>

<?php if (file_exists($clean_fasta)): ?>
<li><a href="<?php echo $clean_fasta; ?>">Cleaned FASTA sequences</a></li>
<?php endif; ?>
</ul>
</section>



<section class="card">
<h3>Sequence Conservation Plot</h3>
<p>The cleaned avain glucose-6-phosphatase protein sequences were aligned to each other using Clustal Omega to generate a multiple sequence alignment (MSA) The MSA was then analysed with EMBOSS plotcon to analyze how strongly conserved each amino acid position is accross the aligned sequences from the precomputed dataset.</p>

<?php if (file_exists($plotcon_png)): ?>
<img class="plot"
     src="<?php echo htmlspecialchars($plotcon_png); ?>"
     alt="Sequence conservation plot for avian glucose-6-phosphatase alignment">
<p>Higher values in the conservation profile indicate regions of the protein alignment that are more strongly conserved across species, which may point to functionally important or structurally constrained regions.</p>
<?php else: ?>
<p class="warning">Conservation plot not found.</p>
<?php endif; ?>

<h3>Analysis Files</h3>
<?php if (file_exists($msa_fasta)): ?>
<li>
<a href="<?php echo htmlspecialchars($msa_fasta); ?>">
Download Clustal Omega multiple sequence alignment
</a>
</li>
<?php endif; ?>

<?php if (file_exists($plotcon_png)): ?>
<li>
<a href="<?php echo htmlspecialchars($plotcon_png); ?>">
Download conservation plot PNG
</a>
</li>
<?php endif; ?>
</ul>
</section>



<section class="card">
<h3>Motif Hits Summary</h3>

<p>
The table below summarises motif hits identified by EMBOSS patmatmotifs in the example
glucose-6-phosphatase bird sequences. Each row corresponds to a detected motif in one
protein sequence.
</p>

<?php if (!empty($motif_rows)): ?>
<table>
<tr>
<th>Accession</th>
<th>Organism</th>
<th>Motif</th>
<th>Start</th>
<th>End</th>
<th>Score</th>
</tr>

<?php foreach ($motif_rows as $row): ?>
<tr>
<td><?php echo htmlspecialchars($row['accession']); ?></td>
<td><?php echo htmlspecialchars($row['organism_name']); ?></td>
<td><?php echo htmlspecialchars($row['motif_name']); ?></td>
<td><?php echo htmlspecialchars($row['start_pos']); ?></td>
<td><?php echo htmlspecialchars($row['end_pos']); ?></td>
<td>
    <?php
    if ($row['hit_score'] !== null) {
        echo htmlspecialchars($row['hit_score']);
    } else {
        echo "N/A";
    }
    ?>
</td>
</tr>
<?php endforeach; ?>
</table>
</section>

<section class="card">
<h3>Biological Context</h3>
<p>Glucose-6-phosphatase is involved in glucose metabolism, and comparing its sequence conservation across birds can help identify regions that are strongly preserved and therefore likely to be functionally important.</p>
</section>
</main>
<?php endif; ?>
<?php include 'footer.php'; ?>

