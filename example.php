<?php include 'header.php';?>

<?php
$example_dir = "data/example";
$motif_dir   = $example_dir . "/motif_outputs";

$raw_fasta      = $example_dir . "/g6_aves.fasta";
$clean_fasta    = $example_dir . "/g6_aves_cleaned.fasta";
$msa_fasta      = $example_dir . "/g6_aves_MSA.fasta";
$plotcon_png    = $example_dir . "/g6_aves_plotcon.png";

$sequence_count = 0;
if (file_exists($clean_fasta)) {
    $lines = file($clean_fasta);
    foreach ($lines as $line) {
        if (strpos($line, '>') === 0) {
            $sequence_count++;
        }
    }
}

$motif_files = [];
if (is_dir($motif_dir)) {
    $motif_files = glob($motif_dir . "/*");
    sort($motif_files);
}
?>

<main class="container">
    <h2>Example Dataset: Glucose-6-Phosphatase Proteins from Aves</h2>

    <p>
        This page demonstrates the full workflow of the website using a precomputed
        example dataset of glucose-6-phosphatase protein sequences retrieved from birds (Aves).
        The sequences were cleaned, aligned with Clustal Omega, analysed for conservation with
        EMBOSS plotcon, and scanned for motifs using EMBOSS patmatmotifs.
    </p>

    <section class="card">
        <h3>Dataset Summary</h3>
        <p><strong>Protein family:</strong> Glucose-6-phosphatase</p>
        <p><strong>Taxonomic group:</strong> Aves</p>
        <p><strong>Number of cleaned sequences:</strong> <?php echo $sequence_count; ?></p>
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

            <?php if (file_exists($msa_fasta)): ?>
                <li><a href="<?php echo $msa_fasta; ?>">Multiple sequence alignment (FASTA)</a></li>
            <?php endif; ?>
        </ul>
    </section>

    <section class="card">
        <h3>Sequence Conservation Plot</h3>
        <?php if (file_exists($plotcon_png)): ?>
            <img src="<?php echo $plotcon_png; ?>" alt="Plotcon conservation plot for glucose-6-phosphatase proteins in Aves" style="max-width:100%; height:auto;">
            <p>
                The plot above shows how sequence conservation varies across the aligned amino acid positions
                of the glucose-6-phosphatase proteins in the selected avian dataset.
            </p>
        <?php else: ?>
            <p>Conservation plot not found.</p>
        <?php endif; ?>
    </section>

    <section class="card">
        <h3>Motif Analysis Outputs</h3>
        <?php if (!empty($motif_files)): ?>
            <p>The files below contain EMBOSS patmatmotifs output for each sequence analysed.</p>
            <ul>
                <?php foreach ($motif_files as $file): ?>
                    <li>
                        <a href="<?php echo $file; ?>">
                            <?php echo htmlspecialchars(basename($file)); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No motif output files were found.</p>
        <?php endif; ?>
    </section>

    <section class="card">
        <h3>Biological Context</h3>
        <p>
            Glucose-6-phosphatase is involved in glucose metabolism, and comparing its sequence conservation across birds can help identify regions that are strongly preserved and therefore likely to be functionally important.
        </p>
    </section>
</main>

<?php include 'footer.php'; ?>

