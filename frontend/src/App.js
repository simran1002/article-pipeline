import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import ArticleList from './components/ArticleList';
import ArticleDetail from './components/ArticleDetail';
import './App.css';

function App() {
  return (
    <Router>
      <div className="App">
        <header className="app-header">
          <div className="container">
            <div>
              <h1>BeyondChats Articles</h1>
              <p className="subtitle">Original and Enhanced Articles</p>
            </div>
            <div className="header-stats">
              <div className="stat-item">
                <span>Live</span>
              </div>
              <div className="stat-item">
                <span>Updated</span>
              </div>
            </div>
          </div>
        </header>
        <main className="main-content">
          <div className="container">
            <Routes>
              <Route path="/" element={<ArticleList />} />
              <Route path="/article/:id" element={<ArticleDetail />} />
            </Routes>
          </div>
        </main>
        <footer className="app-footer">
          <div className="container">
            <p>&copy; 2025 BeyondChats. Assignment Submission.</p>
          </div>
        </footer>
      </div>
    </Router>
  );
}

export default App;


