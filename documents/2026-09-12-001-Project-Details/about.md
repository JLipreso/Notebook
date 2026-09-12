### About

The project is currently MVP called "Notebook" we don't have the final brand name yet, but concept is digitalization of students paper-based notebook integrated with latest technology commonly used by students today.

---

### Initial Features

The following are initial features to be included in the MVP

---

#### **Phase 1 Student App**

1. **Authentication:** For authentication we will integrate Firebase Authentication service to allow authenticate students.
  - **Basic Information:** To create students account we will only require to provide the Firstname, Lastname, Middle name (Optional), Birthday, Email, Mobile Number with optional password.
  - **Geolocation:** We will collect students location in every initialization only or in every first open of the app, no need to keep history just override the previous record.
  - **Address:** We will have a specific table in the database that will list down list of Region, Province, City/Municipality and Barangay. We will use this list to create a dropdown list to complete an address.
2. **Multi Notebook**: Traditionally students purchase 8 to 12 notebook for one school year. The Notebook App offers option to create a notebook for specific school year, once this school year ends they can keep that created notebook as archieve create another notebook for the school year.
  - **Management** - We will have a Notebook Management features to manage multiple notebook which will look like library or gallery.
  - **Sharing** -  We will allow students to share a notebook, specific page only, student can also specify access type like "Read Only", "Read & Write" with option to set expiration of access.
  - **Notebook Type:** We will add features to let students specify the notebook type for example "Composition", "Writing", "Drawing Book", "Diary", "Scrapbook", "Log Book", "Timesheet", please consider that we will be adding new notebook type in the future.
  - **Cover Photo:** The student can upload cover photo for this notebook.
  - **Text Font:** The student can specify family fonts, we will downloading all free fonts that we can use.
3. **Third-Party Integration:** The Notebook App will be integrated with known technology to ease students works.
  - Google Drive - We will integrate Google Drive to allow students upload and download files from their own drive.
  - Google Calendar - We will integrate Google Calendar to manage students schedule and access Google Calendar features.
4. **Image Viewer:** The Notebook App will embed image viewer for all type of image if possible but will prioritize PNG, JPEG, JPG, WEBP, etc.
5. **Document Viewer:** The Notebook App will embed document viewer for all type of document but will prioritize doc, docx, excel files, pdf, etc.
6. **In-App Notification:** We will build in-app notification system for teacher and students, this notification can be sent to multiple recepient with seen logs.
7. **Messenger:** We will be build in-app messaging functionality to allow students and teacher communicate in the app.
  - Group Chat: We will allow students and teacher to create group chat.
  - Private: We will allow traditional one-on-one chat in both teacher to teacher or students to students and vice versa.
  - Content: We will allow stdudents/teachers to send text, images and documents like PDF, doc, docx, excel, etc.
8. **Course Management:** The students will have a course management module where they can access lessons from course they are added by teacher.
  - Invitation: Students have option to accept or decline invite, when declining, they need to provide reason.
  - 
9. **Lesson Management:**
  - Content: We will integrate rich-text editor in read-only since this access is for students only.
  - Status: Each lessons will have start and end date, after student taking this lessons they can declare that they completed it, then related quiz will be activated for them to take.
10. **Quiz Management:**
  - The Quiz is automatically activated once a lessons is completed. They see score right away after submitting it. Students can't see the correct answer until teacher trigger to make correct answers visible. This is suggested to activate after all students completed the exam.
  - The Quiz should handle any type of quiz such as multiple choice, yes/no, etc.
  - Teacher will receive notification once student submitted a quiz
  - Teacher can set optional time limit but the default is 60 minutes or 1 hour.
11. **Exam Management:**
  - The is almost identical but most of its content came from Quiz collections with option to add new questionnaire.
12. **Assignment Management:**
  - We will add features where students can answer and submit assignments.

---

#### Phase 2 Student App (Optional/We can skip it if not feasible)

1. **Document Viewer:** The Notebook App will embed document viewer for 3D Files so that Engineers, Archetics and other professional can use it.
2. **Image Viewer:** In this phase we will consider to allow students edit image if possible.
3. Document Viewer: In this phase we will consider adding features to allow students edit document for doc, docx, txt, md files,

---

#### Phase 3 Teacher App and Portal

The teacher will have 3 type of device, they can access it

1. Course Management: A features where teacher can automate the sharing of courses with the initial guideline as follows:
  - **Invitation**: Teacher can add student to course by invitation, teacher need to input student's email to send invitation. Once student accept or decline the invitation, teacher will receive notification right from the app.
  - **Content**: In the teacher's portal they can manage course including its lessons, quizzes, exam, assignment and students grades.
  - **Reusability**: In teacher's end courses need to be reusable for the next semester or school year, to save teacher's time in creating new courses we will make courses reusable for the next school year.
  - **Course Timeline**: We will add features where teacher can set specific start and end date or validity of this course, once this timeline ends students can't access it and labelled as expired course.
  - **Course Calendar: **We will integrate Calendar view of course with lesson breakdown
2. Lesson Management:
  - **Rich-Text Editor**: To make lessons content more interactive we will use WYSIWYG rich text editor, we will look for rich-text editor that output JSON Format to save in database instead of saving HTML format.
  - **Video Reference**: We will integrate video sharing for lessons but we will only allow YouTube, Web Link, etc. but we will consider in-app uploading features using FTP in the future.
  - **Course Timeline:** As extended features, we will attach calendar system to lessons where teacher can set specific lessons date to be available to students.
  - **AI Assistence:** Allow teacher to use AI to generate lesson contents, this features is need to be available only for
3. Quiz Management:
4. Exam Management:
5. Assignment Management:
6. Grades Management:
7. Attendance Management:

---

#### Phase 4 Admin Panel

This project will have a Admin Panel to manage data, it will be the highest permission.

- Dashboard: A basic analytcial mostly counts such as:
  - Number of total students.
  - Number of new students today.
  - Number of total teachers.
  - Number of new teachers today.
  - Number of pending payments.
- Students Management:
- Teachers Management:
- Payment Management:
  - **New Payments:** This is a dedicated page with table of new unverified payments, each row will have details and action button to confirm the payment and activate teacher/students account.
  - **History:** This is a dedicated page for archive payments completed and yesterday's payments.

> NOTE: The payment will have the following status type:
> **Unverified:** This are new payments not yet verified by staff, in database use "unverified".  
> **In Progress:** When staff started to verify the payment they need to update the status to in progress to notify payee that there is ongoing verification. In the database use "in_progress"  
> **Follow Up:** This is when issue is found and require to communicate with payee to help verify the payment.  
> **Received:** This are payments received and verified by staff, in database use "received".  
> **Fail Payment:** This is a status where staff found out that payment was not receive, in database use "fail_payment".

- Reports and Analytics:

---

#### Project Settings and Configurations

The project features need to be dynamic as possible as follows:

- **Student Pricing:** We will have a 3 tier of pricing called "Free", "Basic" and "Premium", the pricing will be in PHP and USD only for now but we will consider to add another currency in the future.
  - The "Free" is technically a free features limited to 14 days.
  - The "Basic" is paid with initial price of PHP 69 per month.
  - The "Premium" is paid with initial price of PHP 129 per month.
  - For Basic and Premium they will have option to add add-on tool called "Student's AI".
  - We will have option in the Admin Panel to edit this price anytime.
  - The pricing needs to be dynamic for example we have option to add a specific features in Free, Basic or Premium anytime
- **Teacher Pricing:** We will have a 3 tier of pricing called "Free", "Basic" and "Premium", the pricing will be in PHP and USD only for now but we will consider to add another currency in the future.
  - The "Free" is technically a free features limited to 14 days.
  - The "Basic" is paid with initial price of PHP 89 per month.
  - The "Premium" is paid with initial price of PHP 169 per month.
  - For Basic and Premium they will have option to add add-on tool called "Teacher's AI".
  - The pricing needs to be dynamic for example we have option to add a specific features in Free, Basic or Premium anytime.
  - We will have option in the Admin Panel to edit this price anytime.
- **Add-on Tool**: In the future we will implement a feature called "Add-on Tool" with separate price added to their monthly bill.
  - **Student's AI:** In the first release we will integrate AI with initial price of PHP 89 per month.
  - **Teacher's AI**: In the first release we will integrate AI with initial price of PHP 89 per month. This add-on will allow teacher to gene
- **Payment Method:** As of the moment we don't have third-party payment method but we are planning to partner with Paymongo or Maya Business.
  - Temporarily we will just add GCash QR Code in the students and teacher's app where they can scan and pay.
  - This GCash QR Code payment will require Reference Number with optional message.
  - In the admin panel under Payment Management module, all payment will be displayed in a table with "Unverified" status, this subject for verifcation if we receive the payment. Once verified staff will confirm it and will activate teacher/students account.
- **Open API:** This is a future plan but I would like to share the initial idea so that API endpoints can be adjusted for future implementation.
  - The Open API concept will allow outside developer to integrate existing LMS or Learning Management System to connect with Notebook API to manage lessons, quiz, exam, attendance, assignments, acitvity and grades.
  - In the future we will create an official document about the Open API concept.
