#Created the main database for our system.
create database Curaline_System_Db;

#Declared the Curaline_System_Db as our default schema for this connection.
use Curaline_System_Db;

#The creation of the tables that will be used in the Db.
#Staff_Info - Stores the metadata for all staff.
CREATE TABLE staff_info (
	staff_id VARCHAR(15) PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    staff_role ENUM('Dentist', 'Nurse', 'Receptionist', 'Admin') NOT NULL,
    phone_number VARCHAR(20),
    email VARCHAR(150),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

#Staff_Auth - Stores the authentication info for all staff.
CREATE TABLE staff_auth (
    staff_id VARCHAR(15) PRIMARY KEY, 
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(64) NOT NULL, 
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_staff_auth_staff
        FOREIGN KEY (staff_id) REFERENCES staff_info(staff_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

#Patient_Info - Stores the metadata for all patients.
CREATE TABLE patient_info (
    patient_id VARCHAR(15) PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    sex ENUM('Male', 'Female') NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    email VARCHAR(150),
    parish_of_residence VARCHAR(50),
    emergency_contact_name VARCHAR(150),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relationship VARCHAR(50),
    has_allergies TINYINT(1) NOT NULL DEFAULT 0,
    allergy_details VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

#Patient_Auth - Stores the patient info for all staff.
CREATE TABLE patient_auth (
    patient_id VARCHAR(15) PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_patient_auth_patient
        FOREIGN KEY (patient_id) REFERENCES patient_info(patient_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

#Appointments - Stores the information re scheduled visits.
CREATE TABLE appointments (
    appointment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(15) NOT NULL,
    dentist_id VARCHAR(15) NOT NULL,
    booked_by_staff_id VARCHAR(15) NOT NULL,
    scheduled_datetime DATETIME NOT NULL,
    status ENUM('Scheduled', 'Checked-In', 'In-Service', 'Completed', 'Cancelled', 'Missed') NOT NULL DEFAULT 'Scheduled',
    dental_service_type VARCHAR(100) NOT NULL,
    booking_channel VARCHAR(50),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_appt_patient
        FOREIGN KEY (patient_id) REFERENCES patient_info(patient_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_appt_dentist
        FOREIGN KEY (dentist_id) REFERENCES staff_info(staff_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_appt_booked_by
        FOREIGN KEY (booked_by_staff_id) REFERENCES staff_info(staff_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

#-------------------------Placeholder------------------------------#