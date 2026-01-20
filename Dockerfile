# Build Stage
FROM golang:1.22-alpine AS builder

WORKDIR /app

# Install build dependencies
RUN apk add --no-cache git

# Copy go mod and sum files
COPY go.mod go.sum ./
RUN go mod download

# Copy source code
COPY . .

# Build the application
# CGO_ENABLED=0 for static binary (modernc.org/sqlite supports this)
RUN CGO_ENABLED=0 GOOS=linux go build -o fidi ./cmd/server

# Final Stage
FROM alpine:latest

WORKDIR /app

# Install runtime dependencies
RUN apk add --no-cache ca-certificates tzdata bash

# Create database directory
RUN mkdir -p /app/database

# Copy binary from builder
COPY --from=builder /app/fidi /usr/local/bin/fidi

# Copy templates and static files (if they are not embedded, but I will embed them later.
# For now assuming they are on disk as per plan "web/templates").
# Actually, the plan says "web/templates". I should copy them.
COPY web /app/web

# Copy entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Environment variables
ENV PORT=80

# Expose port
EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["fidi"]
