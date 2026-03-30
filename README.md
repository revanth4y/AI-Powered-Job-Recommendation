# AI-Based Job Recommendation System

A full-stack recruitment platform that leverages machine learning, NLP techniques, and secure proctored assessments to deliver personalized job recommendations and reliable candidate evaluation.

---

## Overview

The system replaces traditional keyword-based job matching with a data-driven recommendation engine and integrates secure assessment workflows, enabling accurate candidate-job matching and integrity-focused evaluation.

---

## Key Contributions

- Developed a machine learning-based recommendation engine using Random Forest to improve job matching relevance over keyword-based systems  
- Implemented NLP-based feature extraction using TF-IDF and cosine similarity to compute similarity between candidate profiles and job descriptions  
- Architected a data-driven evaluation pipeline with proctored assessments to ensure reliable and integrity-focused candidate evaluation  
- Designed a secure role-based system with OTP-based authentication and real-time application tracking  
- Implemented strict access control and data protection mechanisms to safeguard sensitive user data and assessment results  
- Improved model performance through feature engineering and data preprocessing, enhancing prediction accuracy and recommendation reliability  
- Built RESTful APIs for job recommendations, assessments, and user workflows  
- Designed a scalable backend using PHP and MySQL with secure database interaction  

---

## System Architecture

### User Interaction Flow

```text
Job Seeker (Resume / Assessment)
        ↓
NLP Processing (TF-IDF + Feature Extraction)
        ↓
ML Model (Random Forest)
        ↓
Matching Score Computation
        ↓
Job Recommendations
        ↓
Admin Monitoring and Audit
```
## Recommendation Engine

- Uses Random Forest for predictive job matching based on:
  - Skills extracted from resumes  
  - Experience level  
  - Assessment performance  

- NLP pipeline:
  - TF-IDF for keyword extraction  
  - Cosine similarity for computing relevance scores  

---

## Data Processing Pipeline

- Resume parsing and normalization  
- Feature engineering for structured model input  
- Data preprocessing to reduce noise and improve model generalization  

---

## Proctoring and Integrity System

- Tab-switch detection using `visibilitychange` event listeners  
- Real-time video monitoring using WebRTC  
- Frame validation using Canvas API  

Ensures:

- Detection of suspicious behavior  
- Secure assessment environment  
- Integrity of evaluation results  

---

## Authentication and Access Control

- OTP-based authentication for secure login  
- Role-based access for:
  - Job seekers  
  - Recruiters  
  - Administrators  

Ensures:

- Controlled system access  
- Secure workflow execution  

---

## Backend and Data Layer

- MySQL database with relational schema for users, jobs, and assessments  
- PDO (PHP Data Objects) used for prepared statements to prevent SQL injection  
- Adheres to PSR coding standards for maintainability  

---

## Recommendation Storage Strategy

- The `ai_recommendations` table acts as a caching layer for similarity scores  
- Stores precomputed recommendations for faster retrieval  
- Periodically updated to balance accuracy and performance  

---

## Tech Stack

- Machine Learning: Python, Scikit-learn (Random Forest)  
- NLP: TF-IDF, Cosine Similarity  
- Backend: PHP  
- Database: MySQL  
- APIs: RESTful services  
- Frontend: HTML, CSS, JavaScript  
- Proctoring: WebRTC, Canvas API  

---

## Performance Considerations

- Improved recommendation accuracy through feature engineering and model tuning  
- Reduced irrelevant matches compared to keyword-based systems  
- Cached recommendation scores to achieve response times of ~100–200ms  
- Optimized database queries for efficient job and user data retrieval  
- Designed to handle moderate concurrent users (~50–100) without degradation  

---

## Limitations

- Model performance depends on input data quality  
- Batch-based model updates (no real-time learning)  
- Limited advanced NLP compared to transformer-based models  

---

## Future Enhancements

- Integration of transformer-based models (BERT) for improved recommendations  
- Real-time model updates using streaming pipelines  
- Advanced resume parsing using deep NLP techniques  
- Enhanced recruiter analytics and explainable recommendations  

---

## Summary

This project demonstrates the integration of machine learning, NLP, and backend system design to build a recommendation-driven recruitment platform. It reflects practical considerations in predictive modeling, secure system design, and scalable application architecture.
