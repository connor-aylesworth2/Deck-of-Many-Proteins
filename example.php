<?php
require 'login.php'; // make pdo connection-containing login.php file mandatory
include 'header.php'; // include header section on example page

/* define example dataset directory */
$example_dir = "data/example";

/* define filepaths for key example outputs */
$raw_fasta   = $example_dir . "/g6_aves.fasta";
$clean_fasta = $example_dir . "/g6_aves_cleaned.fasta";
$msa_fasta   = $example_dir . "/g6_aves_MSA.fasta";
$plotcon_png = $example_dir . "/g6_aves_plotcon.1.png";

/* make placeholders for example job + table contents */
$example_job = null;
$sequence_rows = [];
$motif_rows = [];
$total_seqs = 0;

/* fetch example job */
$job_stmt = $pdo->prepare("SELECT job_id FROM jobs WHERE is_example = 1 LIMIT 1");
$job_stmt->execute();
$example_job = $job_stmt->fetch();

/* if example job exists, fetch its sequence + motif info from mysql */
if ($example_job) {
    $job_id = (int)$example_job['job_id'];

    /* fetch example sequences */
    $seq_stmt = $pdo->prepare("SELECT accession, protein_name, organism_name, sequence_length FROM sequences WHERE job_id = ? ORDER BY organism_name, accession");
    $seq_stmt->execute([$job_id]);
    $sequence_rows = $seq_stmt->fetchAll();

    /* count total seqs from rows returned above */
    $total_seqs = count($sequence_rows);

    /* fetch motif hits for example job */
    $motif_stmt = $pdo->prepare("SELECT s.accession, s.organism_name, mh.motif_name, mh.start_pos, mh.end_pos, mh.hit_score, mh.hit_description FROM motif_hits mh JOIN sequences s ON mh.sequence_id = s.sequence_id WHERE mh.job_id = ? ORDER BY s.organism_name, s.accession, mh.start_pos");
    $motif_stmt->execute([$job_id]);
    $motif_rows = $motif_stmt->fetchAll();
}
?>


<!-- main content container for the full example dataset walkthrough -->
<main class="container">
<section class="card">
<h2>Example Dataset: Glucose-6-Phosphatase Proteins from Aves</h2>
<p>This page demonstrates the full workflow of Deck of Many Proteins using a precomputed example dataset of glucose-6-phosphatase protein sequences from birds (Aves). It is intended as a “try before you buy” example so that users can explore the website’s functionality before submitting their own analyses. </p>
<p>The example dataset was retrieved from NCBI, filtered to remove duplicate or poor-quality entries, aligned with Clustal Omega, analysed for conservation with EMBOSS plotcon, and scanned for known motifs with EMBOSS patmatmotifs.</p>
</section>

<section class="card">
<h3>Precomputed Dataset Summary</h3>
<p><strong>Protein family:</strong> Glucose-6-phosphatase</p>
<p><strong>Taxon group:</strong> Aves</p>
<p><strong>Number of cleaned sequences:</strong> <?php echo htmlspecialchars((string)$total_seqs); ?></p>
</section>

<section class="card">
<h3>Example Sequences</h3>
<p>The table below contains the cleaned glucose-6-phosphatase protein sequences used for the avian example dataset. These sequences were originally retrieved from the NCBI Protein database using the following command:</p>
<p><code>esearch -db protein -query '"glucose-6-phosphatase"[Protein Name] AND Aves[Organism]' | efetch -format fasta &gt; g6_aves.fasta</code></p>

<!-- if example sequences were found, display them in a tidy table -->
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
    <td><?php echo htmlspecialchars($row['protein_name'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($row['organism_name'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars((string)($row['sequence_length'] ?? '')); ?></td>
    </tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p class="warning">No sequences were found for the example dataset.</p>
<?php endif; ?>
</section>

<section class="card">
<h3>Download Files</h3>
<ul>

<!-- raw fasta straight from edirect retrieval -->
<?php if (file_exists($raw_fasta)): ?>
<li>
<a href="<?php echo htmlspecialchars($raw_fasta); ?>">Download raw FASTA sequences</a>
</li>
<?php endif; ?>

<!-- cleaned fasta after duplicate / quality filtering -->
<?php if (file_exists($clean_fasta)): ?>
<li>
<a href="<?php echo htmlspecialchars($clean_fasta); ?>">Download cleaned FASTA sequences</a>
</li>
<?php endif; ?>

<!-- msa output from clustalo -->
<?php if (file_exists($msa_fasta)): ?>
<li>
<a href="<?php echo htmlspecialchars($msa_fasta); ?>">Download Clustal Omega multiple sequence alignment</a>
</li>
<?php endif; ?>

<!-- conservation plot png from emboss plotcon -->
<?php if (file_exists($plotcon_png)): ?>
<li>
<a href="<?php echo htmlspecialchars($plotcon_png); ?>">Download conservation plot PNG</a>
</li>
<?php endif; ?>
</ul>
</section>

<section class="card">
<h3>Sequence Conservation Plot</h3>
<p>The cleaned avian glucose-6-phosphatase protein sequences were aligned using Clustal Omega to generate a multiple sequence alignment (MSA). The alignment was then analysed with EMBOSS plotcon to estimate how strongly each amino acid position is conserved across the example dataset.</p>

<!-- show conservation plot if the png exists where expected -->
<?php if (file_exists($plotcon_png)): ?>
<img
    class="plot"
    src="<?php echo htmlspecialchars($plotcon_png); ?>"
    alt="Sequence conservation plot for avian glucose-6-phosphatase alignment">
<p>Higher values in the conservation profile indicate regions of the alignment that are more strongly preserved across species. Such regions may correspond to functionally important or structurally constrained parts of the protein.</p>
<?php else: ?>
<p class="warning">Conservation plot not found.</p>
<?php endif; ?>
</section>

<section class="card">
<h3>Motif Hits Summary</h3>
<p>The table below summarises motif hits identified by EMBOSS patmatmotifs in the example bird glucose-6-phosphatase sequences. Each row corresponds to a detected motif in one protein sequence.</p>

<!-- motif hit display table populated from mysql motif_hits + sequences join -->
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
    <td><?php echo htmlspecialchars((string)$row['start_pos']); ?></td>
    <td><?php echo htmlspecialchars((string)$row['end_pos']); ?></td>
    <td><?php if ($row['hit_score'] !== null) {
        echo htmlspecialchars((string)$row['hit_score']);}
    else {echo "N/A";}?></td>
    </tr>
<?php endforeach; ?>
</table>

<p>Repeated detection of similar motifs across many bird glucose-6-phosphatase proteins supports the idea that these sequences share a conserved functional role. Differences in motif presence may reflect partial sequences, annotation variation, or genuine biological divergence.</p>
<?php else: ?>
<p class="warning">No motif hits were found for the example dataset.</p>
<?php endif; ?>
</section>

<section class="card">
<h3>Biological Context</h3>
<p>Glucose-6-phosphatase is involved in glucose metabolism. Comparing its sequence conservation across birds can help identify regions that are strongly preserved and are therefore more likely to be functionally important.</p>
<p>Conserved sequence regions may reflect catalytic importance, structural constraint, or shared biological roles across species. Motif analysis provides an additional layer of interpretation by identifying known sequence patterns associated with protein function.</p>
</section>
</main>

<?php include 'footer.php'; ?> <!-- include footer in example page -->
