import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import axios from 'axios';
import './ArticleList.css';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000';

function ArticleList() {
  const [articles, setArticles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchArticles();
  }, []);

  const fetchArticles = async () => {
    try {
      setLoading(true);
      const response = await axios.get(`${API_URL}/api/articles`);
      setArticles(response.data);
      setError(null);
    } catch (err) {
      setError('Failed to load articles. Please make sure the backend is running.');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="loading-container">
        <div className="spinner"></div>
        <p>Loading articles...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="error-container">
        <p className="error-message">{error}</p>
        <button onClick={fetchArticles} className="retry-button">
          Retry
        </button>
      </div>
    );
  }

  return (
    <div className="article-list">
      <div className="list-header">
        <h2>All Articles</h2>
        <p className="article-count">{articles.length} article{articles.length !== 1 ? 's' : ''} found</p>
      </div>

      {articles.length === 0 ? (
        <div className="empty-state">
          <p>No articles found. Please run the scraper to fetch articles.</p>
        </div>
      ) : (
        <div className="articles-grid">
          {articles.map((article) => (
            <Link
              key={article.id}
              to={`/article/${article.id}`}
              className="article-card"
            >
              <div className="article-header">
                <h3 className="article-title">{article.title}</h3>
                {article.is_updated && (
                  <span className="badge updated">Enhanced</span>
                )}
                {!article.is_updated && (
                  <span className="badge original">Original</span>
                )}
              </div>
              
              {article.excerpt && (
                <p className="article-excerpt">{article.excerpt}</p>
              )}
              
              <div className="article-footer">
                <span className="article-date">
                  {new Date(article.created_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                  })}
                </span>
                {article.author && (
                  <span className="article-author">By {article.author}</span>
                )}
              </div>
              <div className="article-card-features">
                {article.original_url && (
                  <span className="feature-tag">🌐 Original Source</span>
                )}
                {article.is_updated && (
                  <span className="feature-tag">✨ Enhanced</span>
                )}
                {article.reference_articles && article.reference_articles.length > 0 && (
                  <span className="feature-tag">📚 {article.reference_articles.length} References</span>
                )}
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}

export default ArticleList;


