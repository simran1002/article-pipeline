import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import axios from 'axios';
import './ArticleDetail.css';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000';

function ArticleDetail() {
  const { id } = useParams();
  const [article, setArticle] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchArticle();
  }, [id]);

  const fetchArticle = async () => {
    try {
      setLoading(true);
      const response = await axios.get(`${API_URL}/api/articles/${id}`);
      setArticle(response.data);
      setError(null);
    } catch (err) {
      setError('Failed to load article. Please make sure the backend is running.');
      console.error('Error fetching article:', err);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="loading-container">
        <div className="spinner"></div>
        <p>Loading article...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="error-container">
        <p className="error-message">{error}</p>
        <Link to="/" className="back-link">← Back to Articles</Link>
      </div>
    );
  }

  if (!article) {
    return (
      <div className="error-container">
        <p className="error-message">Article not found</p>
        <Link to="/" className="back-link">← Back to Articles</Link>
      </div>
    );
  }

  // Parse reference articles if they exist
  const referenceArticles = article.reference_articles || [];

  return (
    <div className="article-detail">
      <Link to="/" className="back-link">← Back to Articles</Link>

      <article className="article-content">
        <header className="article-header">
          <div className="article-badges">
            {article.is_updated && (
              <span className="badge updated">Enhanced Article</span>
            )}
            {!article.is_updated && (
              <span className="badge original">Original Article</span>
            )}
          </div>
          
          <h1 className="article-title">{article.title}</h1>
          
          <div className="article-meta">
            {article.author && (
              <span className="meta-item">
                <strong>Author:</strong> {article.author}
              </span>
            )}
            <span className="meta-item">
              <strong>Published:</strong>{' '}
              {new Date(article.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
              })}
            </span>
            {article.original_url && (
              <a
                href={article.original_url}
                target="_blank"
                rel="noopener noreferrer"
                className="original-link"
              >
                View Original →
              </a>
            )}
          </div>
        </header>

        <div
          className="article-body"
          dangerouslySetInnerHTML={{ __html: article.content }}
        />

        {referenceArticles.length > 0 && (
          <footer className="article-references">
            <h3>References</h3>
            <ul>
              {referenceArticles.map((url, index) => (
                <li key={index}>
                  <a
                    href={url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="reference-link"
                  >
                    {url}
                  </a>
                </li>
              ))}
            </ul>
          </footer>
        )}
      </article>
    </div>
  );
}

export default ArticleDetail;


