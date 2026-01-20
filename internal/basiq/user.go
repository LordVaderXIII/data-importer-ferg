package basiq

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
)

type User struct {
	ID    string `json:"id"`
	Email string `json:"email"`
}

type UserResponse struct {
	ID    string `json:"id"`
	Email string `json:"email"`
}

// CreateUser creates a new user in Basiq (or gets existing if implemented by API, but usually creates new)
// Assuming we create a fresh user for this instance
func (c *Client) CreateUser(email, mobile string) (*User, error) {
	payload := map[string]string{}
	if email != "" {
		payload["email"] = email
	}
	if mobile != "" {
		payload["mobile"] = mobile
	}

	req, err := c.newRequest("POST", "/users", payload)
	if err != nil {
		return nil, err
	}

	resp, err := c.HTTPClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode > 299 {
		body, _ := io.ReadAll(resp.Body)
		return nil, fmt.Errorf("create user failed: %s - %s", resp.Status, string(body))
	}

	var u User
	if err := json.NewDecoder(resp.Body).Decode(&u); err != nil {
		return nil, err
	}

	return &u, nil
}

// GetUser retrieves a user by ID
func (c *Client) GetUser(userID string) (*User, error) {
	req, err := c.newRequest("GET", "/users/"+userID, nil)
	if err != nil {
		return nil, err
	}

	resp, err := c.HTTPClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode > 299 {
		return nil, fmt.Errorf("get user failed: %s", resp.Status)
	}

	var u User
	if err := json.NewDecoder(resp.Body).Decode(&u); err != nil {
		return nil, err
	}

	return &u, nil
}

// GetClientToken returns a token for the frontend (client_access_token)
func (c *Client) GetClientToken(userID string) (string, error) {
	req, err := http.NewRequest("POST", BasiqAuthURL, nil)
	if err != nil {
		return "", err
	}

	req.Header.Set("Authorization", "Basic "+c.APIKey)
	req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
	req.Header.Set("basiq-version", "3.0")

	// We need to write to Body, not Query for POST
	bodyStr := fmt.Sprintf("scope=CLIENT_ACCESS&userId=%s", userID)
	req.Body = io.NopCloser(bytes.NewBufferString(bodyStr))

	resp, err := c.HTTPClient.Do(req)
	if err != nil {
		return "", err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		body, _ := io.ReadAll(resp.Body)
		return "", fmt.Errorf("client auth failed: %s - %s", resp.Status, string(body))
	}

	var tr TokenResponse
	if err := json.NewDecoder(resp.Body).Decode(&tr); err != nil {
		return "", err
	}

	return tr.AccessToken, nil
}
