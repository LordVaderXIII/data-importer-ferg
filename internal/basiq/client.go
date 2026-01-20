package basiq

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"time"
)

const (
	BasiqAuthURL = "https://au-api.basiq.io/token"
	BasiqAPIURL  = "https://au-api.basiq.io"
)

type Client struct {
	APIKey     string
	HTTPClient *http.Client
	Token      string
	TokenExp   time.Time
}

func New(apiKey string) *Client {
	return &Client{
		APIKey: apiKey,
		HTTPClient: &http.Client{
			Timeout: 30 * time.Second,
		},
	}
}

type TokenResponse struct {
	AccessToken string `json:"access_token"`
	ExpiresIn   int    `json:"expires_in"`
	TokenType   string `json:"token_type"`
}

func (c *Client) Authenticate() error {
	if c.Token != "" && time.Now().Before(c.TokenExp) {
		return nil
	}

	req, err := http.NewRequest("POST", BasiqAuthURL, nil)
	if err != nil {
		return err
	}

	req.Header.Set("Authorization", "Basic "+c.APIKey)
	req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
	req.Header.Set("basiq-version", "3.0")

	resp, err := c.HTTPClient.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		body, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("auth failed: %s - %s", resp.Status, string(body))
	}

	var tr TokenResponse
	if err := json.NewDecoder(resp.Body).Decode(&tr); err != nil {
		return err
	}

	c.Token = tr.AccessToken
	// Subtract a buffer from expires_in (usually 3600s)
	c.TokenExp = time.Now().Add(time.Duration(tr.ExpiresIn-60) * time.Second)

	return nil
}

func (c *Client) newRequest(method, path string, body interface{}) (*http.Request, error) {
	if err := c.Authenticate(); err != nil {
		return nil, err
	}

	var bodyReader io.Reader
	if body != nil {
		jsonBody, err := json.Marshal(body)
		if err != nil {
			return nil, err
		}
		bodyReader = bytes.NewBuffer(jsonBody)
	}

	req, err := http.NewRequest(method, BasiqAPIURL+path, bodyReader)
	if err != nil {
		return nil, err
	}

	req.Header.Set("Authorization", "Bearer "+c.Token)
	req.Header.Set("Accept", "application/json")
	if body != nil {
		req.Header.Set("Content-Type", "application/json")
	}

	return req, nil
}
