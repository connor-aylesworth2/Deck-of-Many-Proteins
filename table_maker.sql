
USE s2837739;

DROP TABLE IF EXISTS conservation_scores;
DROP TABLE IF EXISTS motif_hits;
DROP TABLE IF EXISTS job_files;
DROP TABLE IF EXISTS sequences;
DROP TABLE IF EXISTS jobs;

CREATE TABLE jobs (
	job_id INT AUTO_INCREMENT PRIMARY KEY,
	protein_family VARCHAR(255) NOT NULL,
	taxon_group VARCHAR(255) NOT NULL,
	search_query TEXT NOT NULL,
	status ENUM('queued', 'running', 'complete', 'failed') NOT NULL DEFAULT 'queued',
	is_example BOOLEAN NOT NULL DEFAULT FALSE,
	total_seqs INT DEFAULT 0,
	send_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	done_time DATETIME NULL,
	error_message TEXT NULL,
	notes TEXT NULL);

CREATE TABLE sequences (
    sequence_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    accession VARCHAR(100) NOT NULL,
    protein_name VARCHAR(255) NULL,
    organism_name VARCHAR(255) NULL,
    taxon_id INT NULL,
    sequence_length INT NULL,
    fasta_header TEXT NULL,
    sequence_text MEDIUMTEXT NOT NULL,
    source_database VARCHAR(100) DEFAULT 'NCBI Protein',
    retrieved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sequences_job
        FOREIGN KEY (job_id) REFERENCES jobs(job_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    UNIQUE KEY uniq_job_accession (job_id, accession),
    INDEX idx_sequences_job (job_id),
    INDEX idx_sequences_accession (accession));

CREATE TABLE job_files (
    file_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_jobfiles_job
        FOREIGN KEY (job_id) REFERENCES jobs(job_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX idx_jobfiles_job (job_id),
    INDEX idx_jobfiles_type (file_type)
);

CREATE TABLE motif_hits (
    motif_hit_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    sequence_id INT NOT NULL,
    motif_name VARCHAR(255) NOT NULL,
    prosite_accession VARCHAR(50) NULL,
    start_pos INT NOT NULL,
    end_pos INT NOT NULL,
    hit_score DECIMAL(10,4) NULL,
    hit_description TEXT NULL,
    CONSTRAINT fk_motifhits_job
        FOREIGN KEY (job_id) REFERENCES jobs(job_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_motifhits_sequence
        FOREIGN KEY (sequence_id) REFERENCES sequences(sequence_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX idx_motifhits_job (job_id),
    INDEX idx_motifhits_sequence (sequence_id),
    INDEX idx_motifhits_prosite (prosite_accession)
);

CREATE TABLE conservation_scores (
    conservation_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    alignment_position INT NOT NULL,
    conservation_score DECIMAL(6,4) NOT NULL,
    consensus_residue CHAR(1) NULL,
    CONSTRAINT fk_conservation_job
        FOREIGN KEY (job_id) REFERENCES jobs(job_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    UNIQUE KEY uniq_job_position (job_id, alignment_position),
    INDEX idx_conservation_job (job_id)
);
