#Declaring the Curaline_System_Db as our default schema for this connection.
use Curaline_System_Db;

#Using seed data to populate the tables.
INSERT INTO staff_info (staff_id, first_name, last_name, staff_role, phone_number, email)
VALUES
    ("STA0001", "Cura", "Root", "Admin", "1876-348-0001", "root@curaline.com"),
    ("DEN0001", "Andre", "Campbell", "Dentist", "1876-348-0101", "andre.campbell@curaline.com"),
    ("DEN0002", "Sasha", "Williams","Dentist", "1876-348-0102", "sasha.williams@curaline.com"),
    ("DEN0003", "Dwayne", "Brown", "Dentist", "1876-348-0103", "dwayne.brown@curaline.com"),
    ("NUR0001", "Kerry", "Johnson", "Nurse", "1876-348-0201", "kerry.johnson@curaline.com"),
    ("NUR0002", "Latoya", "Miller", "Nurse", "1876-348-0202", "latoya.miller@curaline.com"),
    ("STA0002", "Tanisha", "Reid", "Receptionist", "1876-348-0301", "tanisha.reid@curaline.com");

#All passwords are encoded and stored using a SHA-256 hash.
    INSERT INTO staff_auth (staff_id, username, password_hash)
VALUES
    ("STA0001", "c_root", SHA2("CuraRoot#2025", 256)),
    ("DEN0001", "a_campbell", SHA2("DocAndre#2025", 256)),
    ("DEN0002", "s_williams", SHA2("DocSasha#2025", 256)),
    ("DEN0003", "d_brown", SHA2("DocDwayne#2025", 256)),
    ("NUR0001", "k_johnson", SHA2("NurseKerry#2025", 256)),
    ("NUR0002", "l_miller", SHA2("NurseLatoya#2025", 256)),
    ("STA0002", "t_reid", SHA2("RecTanisha#2025", 256));
    
INSERT INTO patient_info (patient_id, first_name, last_name, date_of_birth, sex, phone_number, email, parish_of_residence,
    emergency_contact_name, emergency_contact_phone, emergency_contact_relationship, has_allergies, allergy_details
)
VALUES
    ("PAT0001", "Jodian", "Clarke", "1995-03-12", "Female", "1876-900-1001", "jodian.clarke@example.com", "Kingston",
     "Marcia Clarke", "1876-555-2001", "Mother", 1, "Allergic to penicillin"),
    ("PAT0002", "Romaine", "Johnson", "1988-07-25", "Male", "1658-195-1002", "romaine.johnson@example.com", "St. Andrew",
     "Peter Johnson", "1876-555-2002", "Father", 0, NULL),
    ("PAT0003", "Tanesha", "Gordon", "2001-11-04", "Female", "1876-720-1003", "tanesha.gordon@example.com", "St. Catherine",
     "Sandra Gordon", "1876-555-2003", "Mother", 0, NULL),
    ("PAT0004", "Malik", "Grant",  "1999-02-19", "Male", "1658-467-1004", "malik.grant@example.com", "Clarendon",
     "Angela Grant", "1876-555-2004", "Aunt", 1, "Allergic to latex"),
    ("PAT0005", "Shanique", "Lewis",  "1993-09-30", "Female", "1876-314-1005", "shanique.lewis@example.com", "St. James",
     "Donna Lewis", "1876-555-2005", "Sister", 0, NULL),
    ("PAT0006", "Omar", "Blake",  "1985-05-08", "Male", "1658-283-1006", "omar.blake@example.com", "Manchester",
     "Andrew Blake", "1876-555-2006", "Brother", 0, NULL);
     
#All passwords are encoded and stored using a SHA-256 hash.
INSERT INTO patient_auth (patient_id, username, password_hash)
VALUES
    ("PAT0001", "j_clarke", SHA2("PatJodian#2025", 256)),
    ("PAT0002", "r_johnson", SHA2("PatRomaine#2025", 256)),
    ("PAT0003", "t_gordon", SHA2("PatTanesha#2025", 256)),
    ("PAT0004", "m_grant", SHA2("PatMalik#2025", 256)),
    ("PAT0005", "s_lewis", SHA2("PatShanique#2025", 256)),
    ("PAT0006", "o_blake", SHA2("PatOmar#2025", 256));

INSERT INTO appointments (patient_id, dentist_id, booked_by_staff_id, scheduled_datetime, 
status, dental_service_type, booking_channel
)
VALUES
    ("PAT0001", "DEN0001", "STA0002", "2025-12-20 09:00:00", 
    "Scheduled", "Cleaning / Scaling", "Phone"),
    ("PAT0002", "DEN0002", "STA0002", "2025-12-20 10:30:00",
     "Scheduled", "Extraction", "Walk-in"),
    ("PAT0005", "DEN0003", "STA0002", "2025-12-21 14:00:00",
     "Scheduled", "Filling", "WhatsApp"),
    ("PAT0003", "DEN0001", "STA0002", "2025-12-22 11:00:00",
     "Scheduled", "Check-up / Examination", "Online Form"),
    ("PAT0004", "DEN0002", "STA0002", "2025-12-23 15:30:00",
     "Scheduled", "Root Canal", "Phone");