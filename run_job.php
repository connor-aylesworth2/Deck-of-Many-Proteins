<?php declare(strict_types=1); //declare strict types for easier debugging
require __DIR__ . '/login.php'; // make pdo-connecting login.php file required


/* argc check */
if ($argc < 2) {fwrite(STDERR, "Usage: php run_job.php <job_id>\n");
    exit(1);
}

/* stores suposed job_id arg and throws an error if job_id is negative */
$job_id = (int)$argv[1];
if ($job_id <= 0) {fwrite(STDERR, "Error: invalid job_id\n");
    exit(1);
}

/* the below vars store absolute filepaths of the tools & languages called below */
$php_bin = '/usr/local/bin/php';
$python_bin = '/usr/bin/python3';
$bash_bin = '/usr/bin/bash';

$esearch_bin = '/home/s2837739/edirect/esearch';
$efetch_bin = '/home/s2837739/edirect/efetch';
$clustalo_bin = '/usr/bin/clustalo';
$plotcon_bin = '/usr/bin/plotcon';

/* clears all previous job_files for new job in case of rewriting */
$clear_files = $pdo->prepare("DELETE FROM job_files WHERE job_id = ?");
$clear_files->execute([$job_id]);



/* ##### Helper Functions ##### */

/* updateJob for updating the jobs table while a job is being processed */
function updateJob(PDO $pdo, int $job_id, array $fields): void {
	
    /* make var to hold job details and another for parameters */	
    $sets = [];
    $params = [':job_id' => $job_id];

    /* builds a details set and parameters array for each column/value pair */
    foreach ($fields as $col => $val) {
        $sets[] = "$col = :$col";
        $params[":$col"] = $val;
    }

    /* joins the job details stored in the generated set with commas, */
    /* makes a query, preps it for pdo, and executes it               */
    $sql = "UPDATE jobs SET " . implode(', ', $sets) . " WHERE job_id = :job_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

/* addJobFile for populating the job_files table with results generated from the analysis */
function addJobFile(PDO $pdo, int $job_id, string $file_type, string $file_path, ?string $description = null): void {

    /* prep pdo statement with ? placeholders and execute with ?s as func. params. */	
    $stmt = $pdo->prepare("INSERT INTO job_files (job_id, file_type, file_path, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$job_id, $file_type, $file_path, $description]);
}

/* debugging function that captures errors of shell commands ran */
function runCommand(string $cmd): void {
    
    /* makes var to store outputs, executes the shell command and forces outputs to $output */
    $output = [];
    exec($cmd . ' 2>&1', $output, $exit_code); 
    
    /* checks if any results and prints them (goes to log through submit.php file) */
    if (!empty($output)) {echo implode("\n", $output) . "\n";}

    /* checks if any out codes and prints them (goes to log through submit.php file */
    if ($exit_code !== 0) {throw new RuntimeException("Command failed: $cmd\n" . implode("\n", $output));}
}



/* ##### Actual Job Processing ##### */

/* begin with try block for error reporting and debugging */
try {

    /* immediately update job record to show that processing started */
    updateJob($pdo, $job_id, ['status' => 'running','error_message' => null,'notes' => 'Pipeline started']);

    /* fetch the newly updated job record (grab the query) */
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE job_id = ?");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();

    /* through error if the job doesn't exist */
    if (!$job) {throw new RuntimeException("Job not found: $job_id");}

    /* extract search_query and define destinations for future results to populate */
    $search_query = $job['search_query'];
    $job_dir = __DIR__ . "/data/jobs/job_" . $job_id;
    $input_dir = $job_dir . "/patmat_input";
    $output_dir = $job_dir . "/patmat_outputs";

    /* makes previously-defined destinations for results if they don't exist */
    if (!is_dir($job_dir)) mkdir($job_dir, 0755, true);
    if (!is_dir($input_dir)) mkdir($input_dir, 0755, true);
    if (!is_dir($output_dir)) mkdir($output_dir, 0755, true);

    /* defining the standardized output file handles for later */
    $raw_fasta = $job_dir . "/raw.fasta";
    $clean_fasta = $job_dir . "/cleaned.fasta";
    $msa_fasta = $job_dir . "/alignment.fasta";
    $plot_prefix = $job_dir . "/plotcon"; // for storage of plotcon's  generated prefix
    $plot_png = $job_dir . "/plotcon.1.png"; // for actual png

    /* Update the page with next steps after beginning run query process */
    updateJob($pdo, $job_id, ['notes' => 'Fetching sequences from NCBI']);

    /* builds the edirect command and runs it by forcing the below format:                          */
    /* proper_esearch -db protein -query <search_query> | proper_efetch -format fasta > <raw_fasta> */
    $fetch_cmd = escapeshellarg($esearch_bin) . " -db protein -query " . escapeshellarg($search_query) . " | " . escapeshellarg($efetch_bin) . " -format fasta > " . escapeshellarg($raw_fasta);
    runCommand($fetch_cmd);

    /* check that the fasta retrieval worked */
    if (!file_exists($raw_fasta) || filesize($raw_fasta) === 0) {throw new RuntimeException("No FASTA data retrieved.");}

    /* add metadata for the raw fasta to job_files table via pdo */
    addJobFile($pdo, $job_id, 'raw_fasta', "data/jobs/job_{$job_id}/raw.fasta", 'Raw fetched FASTA');

    /* update results page with metadta */
    updateJob($pdo, $job_id, ['notes' => 'Cleaning FASTA sequences']);

    /* build the sequence-cleaning command to remove duplicates and poor quality seqs */
    $clean_cmd = escapeshellarg($python_bin) . " " . escapeshellarg(__DIR__ . "/scripts/seq_cleaner.py") . " " . escapeshellarg($raw_fasta) . " " . escapeshellarg($clean_fasta);

/* update pipeline process with seq-cleaning step and run the cleaning step */
echo "Running cleaner command:\n$clean_cmd\n";
runCommand($clean_cmd);

/* debugging for printing full cleaned_fasta errors */
echo "After cleaner:\n"; // timestamp
echo "clean_fasta path: $clean_fasta\n"; // outfile path
echo "exists: " . (file_exists($clean_fasta) ? "yes" : "no") . "\n"; // whether the cleaned_fasta exists
if (file_exists($clean_fasta)) {echo "size: " . filesize($clean_fasta) . "\n";} // size of cleaned_fasta

/* check that cleaned fasta exists once again */
if (!file_exists($clean_fasta) || filesize($clean_fasta) === 0) {throw new RuntimeException("No cleaned FASTA produced.");}

    /* record cleaned_fasta file in job_files mysql table with pdo */
    addJobFile($pdo, $job_id, 'clean_fasta', "data/jobs/job_{$job_id}/cleaned.fasta", 'Cleaned FASTA');

    /* update jobs mysql table notes field */
    updateJob($pdo, $job_id, ['notes' => 'Importing cleaned sequences into database']);

    /* build cleaned seq import command, run it, and import to sequences table */
    $import_seq_cmd = escapeshellarg($php_bin) . " " . escapeshellarg(__DIR__ . "/scripts/populate_sequences.php") . " " . escapeshellarg((string)$job_id) . " " . escapeshellarg($clean_fasta);
    echo "Running sequence import command:\n$import_seq_cmd\n";
runCommand($import_seq_cmd);


    /* counting imported sequences; pulls total # of seqs imported into sequences table with pdo */
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM sequences WHERE job_id = ?");
    $count_stmt->execute([$job_id]);
    $total_seqs = (int)$count_stmt->fetchColumn();

    /* throw informative error if no sequences were imported to sequences mysql table */
    if ($total_seqs === 0) {throw new RuntimeException("No sequences were imported.");}

    /* update job with sequence count and next clustalO steps */
    updateJob($pdo, $job_id, ['total_seqs' => $total_seqs, 'notes' => 'Running Clustal Omega']);

    /* build clustalo command and run it */
    $clustalo_cmd = escapeshellarg($clustalo_bin) . " -i " . escapeshellarg($clean_fasta) . " -o " . escapeshellarg($msa_fasta) . " --force";
    runCommand($clustalo_cmd);

    /* add resulting msa to corresponding job_file entry */
    addJobFile($pdo, $job_id, 'msa_fasta', "data/jobs/job_{$job_id}/alignment.fasta", 'Clustal Omega alignment');

    /* update job with next plotcon steps */
    updateJob($pdo, $job_id, ['notes' => 'Generating conservation plot']);

    /* build plotcon command and run it */
    $plotcon_cmd = "printf '4\n' | " . escapeshellarg($plotcon_bin) . " -sequences " . escapeshellarg($msa_fasta) . " -graph png -goutfile " . escapeshellarg($plot_prefix);
    runCommand($plotcon_cmd);

    /* check if PNG is produced from plotcon command */
    if (file_exists($plot_png)) {addJobFile($pdo, $job_id, 'plotcon_png', "data/jobs/job_{$job_id}/plotcon.1.png", 'Conservation plot');}

    /* update job progress with next sequence separating steps */
    updateJob($pdo, $job_id, ['notes' => 'Preparing motif input FASTA files']);

    /* build sequence separation command and run it */
    $split_cmd = escapeshellarg($python_bin) . " " . escapeshellarg(__DIR__ . "/scripts/seq_separator.py") . " " . escapeshellarg($clean_fasta) . " " . escapeshellarg($input_dir);
    runCommand($split_cmd);

    /* update job progress with next motif analysis steps */
    updateJob($pdo, $job_id, ['notes' => 'Running patmatmotifs']);

    /* build motif analysis command and run it */
    $motif_cmd = escapeshellarg($bash_bin) . " " . escapeshellarg(__DIR__ . "/scripts/patmat.sh") . " " . escapeshellarg($input_dir) . " " . escapeshellarg($output_dir);
    runCommand($motif_cmd);

    /* update job progress with next motif results importation steps */
    updateJob($pdo, $job_id, ['notes' => 'Importing motif hits']);

    /* build command to import motif hits and run it */
    $import_motif_cmd = escapeshellarg($php_bin) . " " . escapeshellarg(__DIR__ . "/scripts/motifs_to_sql.php") . " " . escapeshellarg((string)$job_id) . " " . escapeshellarg($output_dir);
    runCommand($import_motif_cmd);

    /* add motif analysis results to corresponding job_files entry */
    addJobFile($pdo, $job_id, 'motif_dir', "data/jobs/job_{$job_id}/patmat_outputs", 'patmatmotifs outputs');

    /* update job progress with analysis completion message, finish time, and total seqs */
    updateJob($pdo, $job_id, ['status' => 'complete', 'done_time' => date('Y-m-d H:i:s'), 'notes' => "Pipeline complete: {$total_seqs} sequences processed."]);
    echo "Job {$job_id} completed successfully.\n";

/* throwable catch block for regular exceptions + php errors;               */
/* sets status to failed, stores finish time, error message, and exit codes */
} catch (Throwable $e) {
    updateJob($pdo, $job_id, ['status' => 'failed', 'done_time' => date('Y-m-d H:i:s'), 'error_message' => $e->getMessage(), 'notes' => 'Pipeline failed']);
    fwrite(STDERR, "Job failed: " . $e->getMessage() . "\n");
    exit(1);
}
