# Use Python 3.9
FROM python:3.9-slim

# Set working directory
WORKDIR /app

# Copy requirements first (for better caching)
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy the rest of the application
COPY . .

# Expose port 5000
EXPOSE 5000

# Run the Flask app with gunicorn - POINTING TO api_server.py
CMD ["gunicorn", "--bind", "0.0.0.0:5000", "api_server:app"]
