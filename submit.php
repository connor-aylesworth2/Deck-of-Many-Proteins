<?php require 'login.php'; //makes sure pdo connection-having login.php file exists and loads correctly

// makes vars to store errors in, used-submitted protein family, and user-sub. taxon_group
$error = '';
$protein_family = '';
$taxon_group = '';

// submits a job when the user submits something and causes an HTTP POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
 // $_POST = global var from the HTTP POST that holds the user-sub. data
 // gets user-sub. info from $_POST and stores info in two separate vars
 $protein_family = trim($_POST['protein_family'] ?? ''); // remove WS and set '' if empty
 $taxon_group = trim($_POST['taxon_group'] ?? ''); // same as last line but for taxon_group

 // if either input wasn't submitted by user or inputs are huge, throws informative errors 
 if ($protein_family === '' || $taxon_group === '') {
  $error = 'Please provide both a protein family and a taxonomic group.';
 
 } elseif (strlen($protein_family) > 255 || strlen($taxon_group) > 255) {
  $error = 'Input is too long.';
 
 } else { // creates search query-holding var if no issues are in submission info (for run_job.php and jobs table)
  $search_query = "{$protein_family}[Protein Name] AND {$taxon_group}[Organism]";
  
  try { // goes to catch block if any errors occur
   $pdo->beginTransaction(); // starts sql db connection
   $stmt = $pdo->prepare("INSERT INTO jobs (protein_family, taxon_group, search_query, status, is_example) VALUES (?, ?, ?, 'queued', 0)"); // creates prepped sql statement to put a new row into jobs table
   
   $stmt->execute([$protein_family, $taxon_group, $search_query]); // executes the prepped sql pdo and fills the placeholder ?s for protein_fam. tax. and search. in the prepped pdo transaction

   $job_id = (int)$pdo->lastInsertId(); // gets sql-generated job_id int of newly submitted job and makes sure its an int
   $pdo->commit(); // saves the transaction results in the db

   $job_dir = __DIR__ . "/data/jobs/job_" . $job_id; // makes a job filepath in the data/jobs dir. of the website files and stores it in job_dir var

   // throws error if job_dir doesn't exist or doesn't have correct permissions
   if (!is_dir($job_dir) && !mkdir($job_dir, 0755, true)) {
    throw new Exception("Failed to create job directory: $job_dir");
   }
   
   // similar to job_dir above, makes job log filepath and throws error if not made properly
   $log_dir = __DIR__ . "/logs";
   if (!is_dir($log_dir) && !mkdir($log_dir, 0755, true)) {
    throw new Exception("Failed to create log directory: $log_dir");
   }

   $php_bin = '/usr/local/bin/php'; // stores correct php in var to make sure the right php is used (was giving errors otherwise)
   $run_script = __DIR__ . '/run_job.php'; // stores the filepath of the script that runs the analysis
   $log_file = $log_dir . "/job_" . $job_id . ".log"; // stores the job log filepath

   // builds the bash command that runs the analysis; formats the command properly with: <correct php> <run_job filepath> <job_id to run on> <where log file should go>; the run_job.php script reads this input with the sys module; $job_id is known to fit the %d sprintf spec and an int so doesn't need escapeshellarg()
   $cmd = sprintf('%s %s %d > %s 2>&1 &', escapeshellarg($php_bin), escapeshellarg($run_script), $job_id, escapeshellarg($log_file));

   // executes the command built above with $output var receiving the outputs and $return_var getting the exit codes returned from the command
   exec($cmd, $output, $return_var);

   header("Location: job.php?job_id=" . $job_id); // redirects user to results page while job runs
   exit; // exit to avoid more output being sent at this point (errors otherwise)
  
  } catch (Exception $e) { // runs if anything causes an exception in the above try block

  // if the exception happens before the data is saved to sql, undoes partial changes to sql db
  if ($pdo->inTransaction()) { 
    $pdo->rollBack();
   }
   $error = 'Failed to create job: ' . $e->getMessage(); // sends error if exception happens above, maybe fix later to be more informative???
  }
}
}

include 'header.php';?> <!-- puts header on page -->

<!-- displays page for new analyses that allows users to submit the information processed above -->
<main class="container">
<section class="card">
<h2>Run a New Protein Analysis</h2>
<p>Submit a protein family and taxonomic group to fetch sequences, analyze conservation, scan for PROSITE motifs, and store the results.</p>

<!--checks if there are any errors found from running the above code (stored in $error) and displays the error(s) if any exist(s) -->
<?php if ($error !== ''): ?>
<p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<!--makes the actual HTTP form for users to submit their inputs via http POST -->
<form method="post" action="submit.php">

<!-- make a text box for protein family input -->
<label for="protein_family">Protein family</label>
<input
 type="text"
 id="protein_family"
 name="protein_family"
 value="<?php echo htmlspecialchars($protein_family); ?>"
 placeholder="e.g. glucose-6-phosphatase"
 required>

<!-- make a text box for taxon group similar to protein family above -->
<label for="taxon_group">Taxonomic group</label>
<input
 type="text"
 id="taxon_group"
 name="taxon_group"
 value="<?php echo htmlspecialchars($taxon_group); ?>"
 placeholder="e.g. Aves"
 required>

<!-- make button for user to press to submit job -->
<input type="submit" value="Submit Analysis">
</form>

</section>
</main>

<?php include 'footer.php'; ?> <!-- put footer on page -->
