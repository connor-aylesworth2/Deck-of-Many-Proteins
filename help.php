<?php include 'header.php';?> <!-- include header section in help page -->

<main class="container">
<section class="card">
<h2>Help and Biological Context</h2>
<p>Deck of Many Proteins is designed to help users explore how a chosen protein family varies across a selected taxonomic group. The aim is not only to retrieve sequences, but also to provide biologically meaningful information about conservation and motif content across related proteins.</p>
</section>

<section class="card">
<h3>What this website does</h3>
<p>The user provides two pieces of information: a protein family and a taxonomic group. The website then retrieves protein sequences matching that query, processes them, and generates a set of outputs that can help the user interpret how similar those proteins are across species.</p>
<p>In practical terms, this means the website can be used to ask questions such as: “How conserved is this protein across birds?”, “Do these proteins share known motifs?”, or “Are some parts of the sequence more strongly preserved than others?”</p>
</section>

<section class="card">
<h3>Why compare proteins across a taxonomic group?</h3>
<p>Comparing proteins across related organisms can highlight which parts of a sequence are likely to be biologically important. Regions that remain very similar across many species are often under evolutionary constraint, meaning that changes in those regions may disrupt function, stability, or interactions with other molecules.</p>
<p>In contrast, regions that vary more between species may reflect evolutionary divergence, lineage-specific adaptation, or reduced functional constraint. Looking at a protein family within a taxonomic group can therefore help place a protein into an evolutionary and functional context.</p>
</section>

<section class="card">
<h3>What is a multiple sequence alignment?</h3>
<p>A multiple sequence alignment arranges related protein sequences so that equivalent amino acid positions are placed in the same columns. This makes it possible to compare each position across many species at once.</p>
<p>Once an alignment has been produced, it becomes much easier to identify conserved residues, variable regions, and larger patterns such as insertions, deletions, or strongly preserved domains.</p>
</section>

<section class="card">
<h3>What does the conservation plot show?</h3>
<p>The conservation plot provides a summary of how strongly conserved each aligned position is across the chosen dataset. Higher conservation values suggest that an amino acid position is preserved across many of the analysed sequences, while lower values indicate more variation at that position.</p>
<p>Conserved peaks in the plot may correspond to regions that are important for catalytic activity, substrate binding, membrane association, structural stability, or other core biological roles. More variable regions may represent flexible or lineage-specific portions of the protein.</p>
</section>

<section class="card">
<h3>What are motifs, and why are they useful?</h3>
<p>Protein motifs are short sequence patterns associated with known structural or functional features. Some motifs correspond to catalytic residues, ligand-binding regions, targeting signals, or conserved domains that recur across many proteins.</p>
<p>Motif scanning helps answer the question: “Do these proteins contain known sequence signatures that are associated with particular biological functions?” If a motif is repeatedly found across members of the dataset, that provides additional evidence that the proteins share a conserved biological role.</p>
</section>

<section class="card">
<h3>How to interpret the sequence table</h3>
<p>The sequence table lists the proteins that were retrieved and retained for analysis. It gives the accession, protein name, source organism, and sequence length. This information is useful for checking whether the retrieved dataset matches your biological expectations.</p>
<p>For example, the table can reveal whether a dataset contains many partial proteins, unusually short entries, or a wide spread of sequence lengths. Such differences may affect the interpretation of conservation analyses.</p>
</section>

<section class="card">
<h3>Example dataset: glucose-6-phosphatase in birds</h3>
<p>The built-in example dataset uses glucose-6-phosphatase proteins from Aves (birds). This example is intended to demonstrate how the website functions and to provide a biologically interpretable case study.</p>
<p>In such a dataset, one would generally expect to see some regions of strong conservation, because glucose-6-phosphatase performs an important metabolic role. If known motifs are shared across many bird sequences, this further supports the idea that the analysed proteins belong to the same functional family.</p>
</section>

<section class="card">
<h3>Why some jobs may return no results</h3>
<p>Not every protein-family and taxon combination will return usable data. Some search terms may be too broad, too narrow, or may not match the naming conventions used in public databases. In other cases, sequences may exist but may be incomplete, highly fragmented, or poorly represented for the chosen group.</p>
<p>If a search returns no useful results, it may help to try a more specific protein name, a broader taxonomic group, or an alternative but biologically related term.</p>
</section>

<section class="card">
<h3>Biological interpretation: important caution</h3>
<p>Conserved sequence regions often suggest biological importance, but conservation alone does not prove function. Similarly, the detection of a motif suggests a known sequence pattern, but should be interpreted alongside other evidence such as protein annotation, domain architecture, experimental literature, and broader evolutionary context.</p>
<p>This website is therefore best used as a starting point for comparative exploration, hypothesis generation, and functional interpretation, rather than as a final proof of biological mechanism.</p>
</section>
</main>

<?php include 'footer.php'; ?> <!-- incude footer section in help page -->
