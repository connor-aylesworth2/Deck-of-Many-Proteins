#!/usr/bin/sql

INSERT INTO jobs (
    protein_family,
    taxonomic_group,
    search_query,
    status,
    is_example,
    sequence_count,
    completed_at,
    notes) VALUES (
    'glucose-6-phosphatase',
    'Aves',
    'glucose-6-phosphatase AND Aves[Organism]',
    'complete',
    1,
    0,
    NOW(),
    'Precomputed example dataset for website demonstration'
);
