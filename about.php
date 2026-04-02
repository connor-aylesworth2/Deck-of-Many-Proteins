<?php include 'header.php';?> <!-- include header section -->

<main class="container">
<section class="card">
<h2>About This Website</h2>
<p>Deck of Many Proteins was developed as a web-based bioinformatics resource that allows users to run conservation analyses with the same level of cleanliness and robust functionality that a professionally developed tool like NCBI's Blast interface has (perhaps it fails at this, do let me know if so!).</p>
<p>This page provides a technical overview of how the website was implemented.</p>
</section>

<section class="card">
<h3>Project Overview</h3>
<p> The website was designed around a job-based workflow. A user submits a protein family and taxonomic group through the interface, after which the website creates a new job, retrieves the relevant protein sequences, performs downstream analyses, stores the outputs, and makes those outputs available through a dedicated results page.</p>
<p>The system also includes a stored example dataset based on glucose-6-phosphatase proteins from Aves, allowing users to explore the functionality of the website before submitting their own analyses.</p>
</section>

<section class="card">
<h3>Website Architecture</h3>
<p>The site uses PHP to generate the web pages and uses PDO to handle all database interactions.</p>
<p>The website combines PHP pages, a MySQL database, Python and Bash helper scripts, and external command-line bioinformatics tools: the web pages provide the user interface, the database stores job and result data, helper scripts process sequence files, and external tools carry out the core sequence analyses.</p>

<section class="card">
<h3>Database Design</h3>
<p>The database is centred on a jobs table that stores each submitted analysis together with its query, status, timestamps, and summary notes. A sequences table stores the cleaned protein records associated with each job. A job_files table records the paths and types of generated output files, and a motif_hits table stores motif matches linked back to both the job and the relevant sequence.</p>
</section>

<section class="card">
<h3>Analysis Workflow</h3>
<p>Once a job is submitted, the website records it in the database and launches a background workflow. Protein sequences are retrieved from NCBI using edirect. The raw FASTA output is then cleaned and filtered with a python helper script before being imported into the database.</p>
<p>The cleaned dataset is aligned with Clustal Omega, and the resulting alignment is used to generate a conservation plot with EMBOSS plotcon. The individual protein sequences are also separated into single-sequence FASTA files with another python helper script and scanned against PROSITE motifs with EMBOSS patmatmotifs. The outputs of these analyses are stored (in server diskspace) and registered in the database so they can be displayed and downloaded later.</p>
</section>

<section class="card">
<h3>External Tools Used</h3>
<p>Several external tools are integrated into the workflow. NCBI edirect is used to retrieve protein sequences from public databases. Clustal Omega is used to generate multiple sequence alignments. EMBOSS plotcon was used to visualise conservation across aligned positions, and EMBOSS patmatmotifs was used to scan sequences for known PROSITE (EMBOSS motif DB) motifs.</p>
<p>These tools were chosen because they fascilitate sequence retrieval, conservation analysis, and motif scanning tasks in a relatively easily-interpretable manner.</p>
</section>

<section class="card">
<h3>Job History</h3>
<p>User-submitted analyses are stored so they can be revisited later. This was implemented through the job history page and the per-job results pages.</p>
</section>
</section>

<section class="card">
<h3>Design Choices, Development Notes, and Repository Info</h3>
<p>The project was designed to balance biological usefulness with practical web development constraints. A job-based model was chosen because it allows for a pheasable way to revisit prior work. The MySQL database schema was built as is so that jobs, sequences, files, and motif hits could be fetched independently or in a corresponding fashion, as each table is related to at least one other table.</p>

<p><strong>SPECIAL SHOUT OUT</strong> to what were easily the hardests parts of this project: trying to make the site job-oriented through the several php files that talk to each other, and trying to implement a helpful and consistent schema.</p>
<p>The website source code is maintained with Git and GitHub for version control. *LINK*</p>
</section>
</main>

<?php include 'footer.php'; ?> <!-- include footer section -->
