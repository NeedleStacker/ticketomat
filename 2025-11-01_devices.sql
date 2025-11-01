CREATE TABLE devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
);

INSERT INTO devices (name) VALUES
('Ulrich CT Motion'),
('Ulrich MAX2/3'),
('Vernacare Vortex AIR'),
('Vernacare Vortex+'),
('ACIST CVi'),
('Eurosets ECMOLIFE');
