package server

import (
	"net/http"
	"fidi/internal/config"
	"fidi/internal/storage"
)

type Server struct {
	cfg *config.Config
	db  *storage.DB
	router *http.ServeMux
}

func New(cfg *config.Config, db *storage.DB) *Server {
	s := &Server{
		cfg:    cfg,
		db:     db,
		router: http.NewServeMux(),
	}
	s.routes()
	s.StartScheduler() // Start the background scheduler
	return s
}

func (s *Server) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	s.router.ServeHTTP(w, r)
}

func (s *Server) routes() {
	s.router.HandleFunc("/", s.handleIndex)
	s.router.HandleFunc("/connect", s.handleConnect)
	s.router.HandleFunc("/mapping", s.handleMapping)
	s.router.HandleFunc("/sync", s.handleSync)

	// Static files? If needed.
	// fs := http.FileServer(http.Dir("web/static"))
	// s.router.Handle("/static/", http.StripPrefix("/static/", fs))
}
