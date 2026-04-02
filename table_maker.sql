-- **USAGE** run on command line: mysql -u <sql_username> -p <db_for_tables_to_go> < table_maker.sql

-- get db
USE s2837739;

-- get rid of whatever exists already for rebuilding
DROP TABLE IF EXISTS motif_hits;
DROP TABLE IF EXISTS job_files;
DROP TABLE IF EXISTS sequences;
DROP TABLE IF EXISTS jobs;

-- CENTRAL TABLE ALERT***
-- make a jobs table with:
CREATE TABLE jobs (
	job_id INT AUTO_INCREMENT PRIMARY KEY, -- job_id as auto-generated unique int and primary key
	protein_family VARCHAR(255) NOT NULL, -- protein_family as required varchar (max len 255 chars)
	taxon_group VARCHAR(255) NOT NULL, -- taxon_group as required varchar (max len 255)
	search_query TEXT NOT NULL, -- search_query as required text (stores exact query for jobs)
	status ENUM('queued', 'running', 'complete', 'failed') NOT NULL DEFAULT 'queued', -- status as a required condition that's either queued, running, complete, or failed (default queued)
	is_example BOOLEAN NOT NULL DEFAULT FALSE, -- is_example as required either example or not
	total_seqs INT DEFAULT 0, --total_seqs as an int of default 0
	send_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, -- send_time as required DATETIME object
	done_time DATETIME NULL, -- done_time as a DATETIME object
	error_message TEXT NULL, -- error messages as unrequired text
	notes TEXT NULL); -- maybe add a thing for notes later?

-- make a sequences table that stores sequences fetched from NCBI for pipeline intput with:
CREATE TABLE sequences (
    sequence_id INT AUTO_INCREMENT PRIMARY KEY, -- sequence_id as auto-gen. unique int and primary key
    job_id INT NOT NULL, -- job_id as required foreign key int
    accession VARCHAR(100) NOT NULL, -- accession as required varchar of max len 100
    protein_name VARCHAR(255) NULL, -- protein _name as varchar of max len 255
    organism_name VARCHAR(255) NULL, -- organism name as varchar of max length 255
    taxon_id INT NULL, -- taxon_id as an int
    sequence_length INT NULL, -- sequence_length as an int
    fasta_header TEXT NULL, -- fasta_header as text of any length
    sequence_text MEDIUMTEXT NOT NULL, -- sequence_text as required medium_text object max len like 16M
    source_database VARCHAR(100) DEFAULT 'NCBI Protein', -- maybe add a thing for this if more dbs are added later???
    retrieved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, -- retreived_at as required timestamp
    CONSTRAINT fk_sequences_job -- makes sure each seq in seqs table corresponds to some job (FK)
        FOREIGN KEY (job_id) REFERENCES jobs(job_id)
        ON DELETE CASCADE -- if a job tied to seqs is deleted, so too are said tied seqs
        ON UPDATE CASCADE, -- same thing but changes with job_id rather than deleting with job_id
    UNIQUE KEY uniq_job_accession (job_id, accession), -- makes a uniq key out of job_id and accession and makes sure there are no repeats within a job_id's seqs 
    -- makes indexes for job_id and accession for easier fetching in php scripts
    INDEX idx_sequences_job (job_id), 
    INDEX idx_sequences_accession (accession));

-- table that stores all inputs and outputs produced with a job with:
CREATE TABLE job_files (
    file_id INT AUTO_INCREMENT PRIMARY KEY, -- file_id as auto-gen. unique int and primary key
    job_id INT NOT NULL, -- job_id as required int and foreign key
    file_type VARCHAR(100) NOT NULL, -- file_type as required varchar of max len 100
    file_path VARCHAR(500) NOT NULL, -- file_path as requred varchar of max len 500
    description TEXT NULL, -- maybe add a thing that generates a description for each job's files???
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, -- created_at as required timestamp
    CONSTRAINT fk_jobfiles_job -- makes sure every job_file entry corresponds to some job_id
        FOREIGN KEY (job_id) REFERENCES jobs(job_id)
        ON DELETE CASCADE -- deletes corresponding job_files if tied job_id is deleted
        ON UPDATE CASCADE, -- updates corres. job_files if tied job_id is changed
    -- makes indeces for job_id and file_type
    INDEX idx_jobfiles_job (job_id),
    INDEX idx_jobfiles_type (file_type)
);

-- table that stores all results of patmatmotif analysis per job with:
CREATE TABLE motif_hits (
    motif_hit_id INT AUTO_INCREMENT PRIMARY KEY, -- motif_hit_id as auto-gen. unique int and primary key
    job_id INT NOT NULL, -- job_id as required int foreign key
    sequence_id INT NOT NULL, -- sequence_id as required int foreign key
    motif_name VARCHAR(255) NOT NULL, -- motif_name as required varchar of max len 255
    start_pos INT NOT NULL, -- start_pos(ition) as required int
end_pos INT NOT NULL, -- end_pos(ition) as required int
    hit_score DECIMAL(10,4) NULL, -- hit_score as required decimal of 10 digits, 4 after decimal point
    hit_description TEXT NULL, -- maybe add a thing later for descriptive motif report???
    CONSTRAINT fk_motifhits_job -- makes sure every motif hit corresponds to a job_id
        FOREIGN KEY (job_id) REFERENCES jobs(job_id)
        ON DELETE CASCADE -- deletes corres. motifs if job_id is deleted
        ON UPDATE CASCADE, -- same thing as last line but updates
    CONSTRAINT fk_motifhits_sequence -- makes sure every motif hit corresponds to sequence_id
        FOREIGN KEY (sequence_id) REFERENCES sequences(sequence_id)
        --same stuff as with job_id above but with sequence_id tied to motif hit
	ON DELETE CASCADE
        ON UPDATE CASCADE,
    -- indeces for job_id and seq_id
    INDEX idx_motifhits_job (job_id),
    INDEX idx_motifhits_sequence (sequence_id),
);

