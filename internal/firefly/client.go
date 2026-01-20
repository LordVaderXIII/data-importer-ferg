package firefly

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"time"
)

type Client struct {
	URL         string
	AccessToken string
	HTTPClient  *http.Client
}

func New(url, token string) *Client {
	return &Client{
		URL:         url,
		AccessToken: token,
		HTTPClient: &http.Client{
			Timeout: 30 * time.Second,
		},
	}
}

func (c *Client) newRequest(method, path string, body interface{}) (*http.Request, error) {
	var bodyReader io.Reader
	if body != nil {
		jsonBody, err := json.Marshal(body)
		if err != nil {
			return nil, err
		}
		bodyReader = bytes.NewBuffer(jsonBody)
	}

	// Ensure URL doesn't have trailing slash
	baseURL := c.URL
	if len(baseURL) > 0 && baseURL[len(baseURL)-1] == '/' {
		baseURL = baseURL[:len(baseURL)-1]
	}

	req, err := http.NewRequest(method, baseURL+"/api/v1"+path, bodyReader)
	if err != nil {
		return nil, err
	}

	req.Header.Set("Authorization", "Bearer "+c.AccessToken)
	req.Header.Set("Accept", "application/json")
	if body != nil {
		req.Header.Set("Content-Type", "application/json")
	}

	return req, nil
}

type Account struct {
	ID         string `json:"id"`
	Attributes struct {
		Name          string `json:"name"`
		Type          string `json:"type"`
		CurrentBalance string `json:"current_balance"`
	} `json:"attributes"`
}

type AccountListResponse struct {
	Data []Account `json:"data"`
}

func (c *Client) GetAccounts() ([]Account, error) {
	req, err := c.newRequest("GET", "/accounts?type=asset", nil)
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
		return nil, fmt.Errorf("firefly get accounts failed: %s - %s", resp.Status, string(body))
	}

	var list AccountListResponse
	if err := json.NewDecoder(resp.Body).Decode(&list); err != nil {
		return nil, err
	}

	return list.Data, nil
}

type Transaction struct {
	Type          string `json:"type"` // withdrawal, deposit
	Date          string `json:"date"`
	Amount        string `json:"amount"`
	Description   string `json:"description"`
	SourceID      string `json:"source_id,omitempty"`
	DestinationID string `json:"destination_id,omitempty"`
	ExternalID    string `json:"external_id,omitempty"` // Use for dedup
}

type TransactionPayload struct {
	Transactions []Transaction `json:"transactions"`
}

func (c *Client) CreateTransaction(tx Transaction) error {
	payload := TransactionPayload{
		Transactions: []Transaction{tx},
	}

	req, err := c.newRequest("POST", "/transactions", payload)
	if err != nil {
		return err
	}

	resp, err := c.HTTPClient.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode == 422 {
		// Duplicate?
		// Firefly returns 422 if duplicate detection is strict?
		// Or maybe success but with warning.
		// For now, treat 422 as error but log body.
		body, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("validation error (duplicate?): %s", string(body))
	}

	if resp.StatusCode > 299 {
		body, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("create transaction failed: %s - %s", resp.Status, string(body))
	}

	return nil
}
