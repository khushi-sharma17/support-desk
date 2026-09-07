import { useEffect, useState } from 'react'
import './TicketDetail.css'

const API_BASE_URL = 'http://localhost/support-desk/backend/web/v1'


function TicketDetail({ ticketId, token, onBack }) {
  const [ticket, setTicket] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  const [user, setUser] = useState(null)

  const [comments, setComments] = useState([])
  const [commentsLoading, setCommentsLoading] = useState(true)
  const [commentBody, setCommentBody] = useState('')
  const [commentType, setCommentType] = useState('public')
  const [commentSubmitting, setCommentSubmitting] = useState(false)
  const [commentMessage, setCommentMessage] = useState('')

  const [agents, setAgents] = useState([])
  const [selectedAgent, setSelectedAgent] = useState('')
  const [assignmentLoading, setAssignmentLoading] = useState(false)
  const [assignmentMessage, setAssignmentMessage] = useState('')
  const [assignmentError, setAssignmentError] = useState('')

  const [activities, setActivities] = useState([])
  const [activityLoading, setActivityLoading] = useState(true)

  const [selectedFile, setSelectedFile] = useState(null)
  const [uploading, setUploading] = useState(false)
  const [uploadMessage, setUploadMessage] = useState('')

  const [analyzing, setAnalyzing] = useState(false)
  const [aiMessage, setAiMessage] = useState('')

  /*
   * Load ticket details.
   */
  useEffect(() => {
    const loadTicket = async () => {
      if (!ticketId || !token) {
        return
      }

      setLoading(true)
      setError('')

      try {
        console.log('TicketDetail token:', token)
        console.log('TicketDetail ticketId:', ticketId)


        const response = await fetch(
          `${API_BASE_URL}/ticket/${ticketId}`,
          {
            method: 'GET',
            headers: {
              Accept: 'application/json',
              Authorization: `Bearer ${token}`,
            },
          }
        )

        const data = await response.json()

        if (!response.ok) {
          throw new Error(
            data.message || 'Unable to load ticket.'
          )
        }

        setTicket(data)
      } catch (err) {
        setError(
          err.message || 'Unable to load ticket.'
        )
      } finally {
        setLoading(false)
      }
    }

    loadTicket()
  }, [ticketId, token])



  
  useEffect(() => {
    if (ticket) {
      console.log('TICKET ASSIGNMENT:', ticket.assigned_to)
      console.log('AVAILABLE AGENTS:', agents)

      setSelectedAgent(
        ticket.assigned_to
          ? String(ticket.assigned_to)
          : ''
      )
    }
  }, [ticket, agents])




    /*
   * Load ticket comments.
   */
  const loadComments = async () => {
    if (!ticketId || !token) {
      return
    }

    setCommentsLoading(true)

    try {
      const response = await fetch(
        `${API_BASE_URL}/ticket/${ticketId}/comments`,
        {
          method: 'GET',
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
          },
        }
      )

      const data = await response.json()

      if (!response.ok) {
        throw new Error(
          data.message || 'Unable to load comments.'
        )
      }

      setComments(Array.isArray(data) ? data : [])
    } catch (err) {
      console.error('Unable to load comments:', err)

      setComments([])
    } finally {
      setCommentsLoading(false)
    }
  }


  /*
  * Load ticket activity timeline.
  */
  const loadActivity = async () => {
    if (!ticketId || !token) {
      return
    }

    setActivityLoading(true)

    try {
      const response = await fetch(
        `${API_BASE_URL}/ticket/${ticketId}/activity`,
        {
          method: 'GET',
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
          },
        }
      )

      const data = await response.json()

      if (!response.ok) {
        throw new Error(
          data.message || 'Unable to load activity.'
        )
      }

      setActivities(
        Array.isArray(data) ? data : []
      )
    } catch (err) {
      console.error(
        'Unable to load activity:',
        err
      )

      setActivities([])
    } finally {
      setActivityLoading(false)
    }
  }


  const loadAgents = async () => {
    if (!token) {
      return
    }

    try {
      const response = await fetch(
        `${API_BASE_URL}/user`,
        {
          method: 'GET',
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
          },
        }
      )

      const data = await response.json()

      if (!response.ok) {
        throw new Error(
          data.message || 'Unable to load staff members.'
        )
      }

      const agentList = Array.isArray(data)
        ? data.filter((user) => user.role === 'agent')
        : []

      setAgents(agentList)
    } catch (err) {
      console.error(
        'Unable to load staff members:',
        err
      )

      setAgents([])
    }
  }


  const handleAssign = async () => {
    setAssignmentLoading(true)
    setAssignmentMessage('')
    setAssignmentError('')

    try {
      const response = await fetch(
        `${API_BASE_URL}/ticket/${ticketId}/assign`,
        {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({
            assigned_to:
              selectedAgent === ''
                ? null
                : Number(selectedAgent),
          }),
        }
      )

      const data = await response.json()

      if (!response.ok) {
        throw new Error(
          data.message || 'Unable to update assignment.'
        )
      }

      setAssignmentMessage(
        selectedAgent === ''
          ? 'Ticket unassigned successfully.'
          : 'Ticket assigned successfully.'
      )

      // Refresh ticket details
      const ticketResponse = await fetch(
        `${API_BASE_URL}/ticket/${ticketId}`,
        {
          method: 'GET',
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
          },
        }
      )

      const updatedTicket = await ticketResponse.json()

      if (ticketResponse.ok) {
        setTicket(updatedTicket)
      }

      await loadActivity()
    } catch (err) {
      console.error(
        'Unable to update assignment:',
        err
      )

      setAssignmentError(
        err.message || 'Unable to update assignment.'
      )
    } finally {
      setAssignmentLoading(false)
    }
  }


  /*
   * Load comments when ticket changes.
   */
  useEffect(() => {
    loadComments()
    loadActivity()
    loadAgents()
  }, [ticketId, token])




  useEffect(() => {
    const storedUser = localStorage.getItem('supportDeskUser')

    if (storedUser) {
      try {
        setUser(JSON.parse(storedUser))
      } catch (err) {
        console.error('Unable to read logged-in user:', err)
        setUser(null)
      }
    }
  }, [])


  /*
   * Add a public reply or internal note.
   */
  const handleAddComment = async () => {
    const trimmedBody = commentBody.trim()

    if (!trimmedBody) {
      setError('Comment cannot be empty.')
      return
    }

    setCommentSubmitting(true)
    setError('')
    setCommentMessage('')

    try {
      const response = await fetch(
        `${API_BASE_URL}/ticket/${ticketId}/add-comment`,
        {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({
            body: trimmedBody,
            type: commentType,
          }),
        }
      )

      const data = await response.json()

      if (!response.ok) {
        throw new Error(
          data.message || 'Unable to add comment.'
        )
      }

      setCommentBody('')
      setCommentMessage(
        commentType === 'internal'
          ? 'Internal note added successfully.'
          : 'Public reply added successfully.'
      )

      await loadComments()

    } catch (err) {
      setError(
        err.message || 'Unable to add comment.'
      )
    } finally {
      setCommentSubmitting(false)
    }
  }


  /*
   * Handle file selection.
   */
  const handleFileChange = (event) => {
    const file = event.target.files?.[0] || null

    setSelectedFile(file)
    setUploadMessage('')
    setError('')
  }


  /*
   * Upload attachment.
   */
  const handleUpload = async () => {
    if (!selectedFile) {
      setError('Please select a file first.')
      return
    }

    setUploading(true)
    setError('')
    setUploadMessage('')

    try {
      const formData = new FormData()

      formData.append('file', selectedFile)

      const response = await fetch(
        `${API_BASE_URL}/ticket/${ticketId}/upload`,
        {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
          },
          body: formData,
        }
      )

      const data = await response.json()

      if (!response.ok) {
        throw new Error(
          data.message || 'Unable to upload attachment.'
        )
      }

      setUploadMessage(
        'Attachment uploaded successfully.'
      )

      setSelectedFile(null)

      /*
       * Refresh ticket so the latest attachment
       * information is displayed.
       */
      const ticketResponse = await fetch(
        `${API_BASE_URL}/ticket/${ticketId}`,
        {
          method: 'GET',
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
          },
        }
      )

      const updatedTicket = await ticketResponse.json()

      if (ticketResponse.ok) {
        setTicket(updatedTicket)
      }

    } catch (err) {
      setError(
        err.message || 'Unable to upload attachment.'
      )
    } finally {
      setUploading(false)
    }
  }


  /*
   * Analyze ticket using Groq AI.
   *
   * Backend flow:
   *
   * React
   *   ↓
   * TicketController
   *   ↓
   * Prompt Guard
   *   ↓
   * GPT-OSS-20B
   *   ↓
   * Summary / Category / Priority
   */
  const handleAnalyze = async () => {
    if (!ticketId || !token) {
      setError(
        'You must be logged in to analyze a ticket.'
      )
      return
    }

    setAnalyzing(true)
    setError('')
    setAiMessage('')

    try {
      const response = await fetch(
        `${API_BASE_URL}/ticket/${ticketId}/analyze`,
        {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
          },
        }
      )

      const data = await response.json()

      if (!response.ok) {
        throw new Error(
          data.message ||
          'Unable to analyze this ticket.'
        )
      }

      if (data.success === false) {
        throw new Error(
          data.message ||
          'Unable to analyze this ticket.'
        )
      }

      setAiMessage(
        'Ticket analyzed successfully.'
      )

      /*
       * Refresh ticket so the newly saved
       * AI summary/category/priority appear.
       */
      const ticketResponse = await fetch(
        `${API_BASE_URL}/ticket/${ticketId}`,
        {
          method: 'GET',
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
          },
        }
      )

      const updatedTicket = await ticketResponse.json()

      if (!ticketResponse.ok) {
        throw new Error(
          updatedTicket.message ||
          'AI analysis succeeded, but the ticket could not be refreshed.'
        )
      }

      setTicket(updatedTicket)

    } catch (err) {
      setError(
        err.message ||
        'Unable to analyze this ticket.'
      )
    } finally {
      setAnalyzing(false)
    }
  }


  /*
   * Download attachment.
   */
  const handleDownload = async () => {
    if (!ticket?.attachment_path) {
      setError(
        'No attachment is available for this ticket.'
      )
      return
    }

    try {
      setError('')

      const response = await fetch(
        `${API_BASE_URL}/ticket/${ticketId}/download`,
        {
          method: 'GET',
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      )

      if (!response.ok) {
        let message =
          'Unable to download attachment.'

        try {
          const data = await response.json()

          message =
            data.message || message
        } catch {
          // Response was not JSON.
        }

        throw new Error(message)
      }

      const blob = await response.blob()

      const downloadUrl =
        window.URL.createObjectURL(blob)

      const link =
        document.createElement('a')

      link.href = downloadUrl

      link.download =
        ticket.attachment_path
          .split('/')
          .pop()

      document.body.appendChild(link)

      link.click()

      link.remove()

      window.URL.revokeObjectURL(downloadUrl)

    } catch (err) {
      setError(
        err.message ||
        'Unable to download attachment.'
      )
    }
  }


  /*
   * Loading state.
   */
  if (loading) {
    return (
      <section className="content">

        <div className="empty-state">

          <div className="loading-circle"></div>

          <p>
            Loading ticket...
          </p>

        </div>

      </section>
    )
  }


  /*
   * Error state.
   */
  if (error) {
    return (
      <section className="content">

        <button
          className="refresh-button"
          onClick={onBack}
        >
          ← Back to tickets
        </button>

        <div className="dashboard-error">
          {error}
        </div>

      </section>
    )
  }


  /*
   * Ticket not found.
   */
  if (!ticket) {
    return (
      <section className="content">

        <button
          className="refresh-button"
          onClick={onBack}
        >
          ← Back to tickets
        </button>

        <div className="empty-state">

          <h3>
            Ticket not found
          </h3>

          <p>
            The requested ticket could not be found.
          </p>

        </div>

      </section>
    )
  }


  /*
   * Ticket detail page.
   */
  return (
    <section className="content">

      <div className="welcome-row">

        <div>

          <div className="section-label">
            TICKET
          </div>

          <h1>
            Ticket #{ticket.id}
          </h1>

          <p>
            View ticket details and customer information.
          </p>

        </div>

        <button
          className="refresh-button"
          onClick={onBack}
        >
          ← Back to tickets
        </button>

      </div>


      <div className="tickets-card">

        <div className="tickets-header">

          <div>

            <h2>
              {ticket.subject || 'No subject'}
            </h2>

            <p>
              Ticket #{ticket.id}
            </p>

          </div>

        </div>


        <div className="ticket-detail">

          <div className="ticket-detail-row">

            <strong>
              Status
            </strong>

            <span>
              {ticket.status || 'Unknown'}
            </span>

          </div>


          <div className="ticket-detail-row">

            <strong>
              Priority
            </strong>

            <span>
              {ticket.priority || 'Unknown'}
            </span>

          </div>


          <div className="ticket-detail-row">

            <strong>
              Category
            </strong>

            <span>
              {ticket.category || 'Not categorized'}
            </span>

          </div>


          <div className="ticket-detail-row">

            <strong>
              Created
            </strong>

            <span>
              {ticket.created_at || '-'}
            </span>

          </div>



          <div className="ticket-detail-row">
            <strong>SLA</strong>

            <span>
              {ticket.sla_hours
                ? `${ticket.sla_hours} hours`
                : '-'}
            </span>
          </div>

          <div className="ticket-detail-row">
            <strong>Due At</strong>

            <span>
              {ticket.due_at || '-'}
            </span>
          </div>

          <div className="ticket-detail-row">
            <strong>SLA Status</strong>

            <span
              className={`sla-status sla-${ticket.sla_status || 'unknown'}`}
            >
              {ticket.sla_status === 'within_sla'
                ? 'Within SLA'
                : ticket.sla_status === 'breached'
                  ? 'SLA Breached'
                  : ticket.sla_status === 'resolved'
                    ? 'Resolved'
                    : 'No Deadline'}
            </span>
          </div>



          <div className="ticket-detail-row">
            <strong>Escalation</strong>
            <span
              className={`sla-status escalation-${ticket.escalation_status || 'none'}`}
            >
              {ticket.escalation_status === 'escalated'
                ? 'Escalated'
                : ticket.escalation_status === 'at_risk'
                  ? 'At Risk'
                  : ticket.escalation_status === 'normal'
                    ? 'Normal'
                    : 'Not Required'}
            </span>
          </div>



          {/* =========================
              ASSIGNMENT
            ========================= */}

          <div className="ticket-assignment">

            <h3>
              Assignment
            </h3>

            <div className="assignment-current">

              <strong>
                Assigned Staff
              </strong>

              <span>
                {ticket.assigned_to
                  ? (
                    agents.find(
                      (agent) =>
                        Number(agent.id) ===
                        Number(ticket.assigned_to)
                    )?.email ||
                    `User #${ticket.assigned_to}`
                  )
                  : 'Unassigned'}
              </span>

            </div>

            {user?.role === 'admin' && (
              <div className="assignment-form">

                <select
                  value={selectedAgent}
                  onChange={(event) =>
                    setSelectedAgent(event.target.value)
                  }
                  disabled={assignmentLoading}
                >

                  <option value="">
                    Unassigned
                  </option>

                  {agents.map((agent) => (
                    <option
                      key={agent.id}
                      value={agent.id}
                    >
                      {agent.email}
                    </option>
                  ))}

                </select>

                <button
                  type="button"
                  className="refresh-button"
                  onClick={handleAssign}
                  disabled={assignmentLoading}
                >
                  {assignmentLoading
                    ? 'Saving...'
                    : 'Save Assignment'}
                </button>

              </div>
            )}

            {assignmentMessage && (
              <div className="success-message">
                {assignmentMessage}
              </div>
            )}

            {assignmentError && (
              <div className="error-message">
                {assignmentError}
              </div>
            )}

          </div>


          <div className="ticket-description">

            <h3>
              Description
            </h3>

            <p>
              {ticket.description ||
                'No description provided.'}
            </p>

          </div>


          
          {/* =========================
          CONVERSATION
          ========================= */}

          <div className="ticket-conversation">

            <h3>
              Conversation
            </h3>

            {commentsLoading ? (
              <p>
                Loading conversation...
              </p>
            ) : comments.length === 0 ? (
              <p>
                No comments yet.
              </p>
            ) : (
              <div className="comments-list">

                {comments.map((comment) => (
                  <div
                    key={comment.id}
                    className={`comment-item ${
                      comment.type === 'internal'
                        ? 'comment-internal'
                        : 'comment-public'
                    }`}
                  >

                    <div className="comment-header">

                      <strong>
                        {comment.user?.email ||
                          'Unknown user'}
                      </strong>

                      <span>
                        {comment.type === 'internal'
                          ? 'Internal Note'
                          : 'Public Reply'}
                      </span>

                    </div>

                    <p>
                      {comment.body}
                    </p>

                    <small>
                      {comment.created_at}
                    </small>

                  </div>
                ))}

              </div>
            )}


            <div className="comment-form">

              <h4>
                Add Comment
              </h4>

              <select
                value={commentType}
                onChange={(event) =>
                  setCommentType(event.target.value)
                }
                disabled={commentSubmitting}
              >
                <option value="public">
                  Public Reply
                </option>

                <option value="internal">
                  Internal Note
                </option>
              </select>

              <textarea
                value={commentBody}
                onChange={(event) =>
                  setCommentBody(event.target.value)
                }
                placeholder={
                  commentType === 'internal'
                    ? 'Write an internal note...'
                    : 'Write a public reply...'
                }
                disabled={commentSubmitting}
                rows={5}
              />

              <button
                type="button"
                className="refresh-button"
                onClick={handleAddComment}
                disabled={
                  commentSubmitting ||
                  !commentBody.trim()
                }
              >
                {commentSubmitting
                  ? 'Adding...'
                  : 'Add Comment'}
              </button>

              {commentMessage && (
                <div className="success-message">
                  {commentMessage}
                </div>
              )}

            </div>

          </div>



          {/* =========================
              ACTIVITY TIMELINE
            ========================= */}

          <div className="ticket-activity">

            <h3>
              Activity Timeline
            </h3>

            {activityLoading ? (
              <p>
                Loading activity...
              </p>
            ) : activities.length === 0 ? (
              <p>
                No activity recorded yet.
              </p>
            ) : (
              <div className="activity-list">

                {activities.map((activity) => (
                  <div
                    key={activity.id}
                    className="activity-item"
                  >

                    <div className="activity-marker"></div>

                    <div className="activity-content">

                      <div className="activity-header">

                        <strong>
                          {activity.user?.email ||
                            'Unknown user'}
                        </strong>

                        <small>
                          {activity.created_at}
                        </small>

                      </div>

                      <p>
                        {activity.action === 'status_changed'
                          ? `Status changed from "${activity.old_value}" to "${activity.new_value}".`
                          : activity.action === 'public_comment_added'
                            ? 'Public reply added.'
                            : activity.action === 'internal_comment_added'
                              ? 'Internal note added.'
                              : activity.action === 'ticket_assigned'
                                ? `Ticket assigned to ${activity.new_value}.`
                                : activity.action === 'ticket_unassigned'
                                  ? 'Ticket unassigned.'
                                  : activity.action === 'ticket_reassigned'
                                    ? `Ticket reassigned to ${activity.new_value}.`
                                    : activity.action}
                      </p>

                    </div>

                  </div>
                ))}

              </div>
            )}

          </div>



          {/* =========================
              AI ANALYSIS
             ========================= */}

          <div className="ticket-ai">

            <h3>
              AI Analysis
            </h3>

            <p>
              Use Groq AI to summarize and
              categorize this ticket.
            </p>

            <button
              type="button"
              className="refresh-button"
              onClick={handleAnalyze}
              disabled={analyzing}
            >
              {analyzing
                ? 'Analyzing...'
                : 'Analyze with AI'}
            </button>

            {aiMessage && (
              <div className="success-message">
                {aiMessage}
              </div>
            )}

            {ticket.ai_summary && (
              <div className="ai-result">

                <div className="ticket-detail-row">

                  <strong>
                    AI Summary
                  </strong>

                  <span>
                    {ticket.ai_summary}
                  </span>

                </div>

                <div className="ticket-detail-row">

                  <strong>
                    AI Category
                  </strong>

                  <span>
                    {ticket.category ||
                      'Not categorized'}
                  </span>

                </div>

                <div className="ticket-detail-row">

                  <strong>
                    AI Priority
                  </strong>

                  <span>
                    {ticket.priority ||
                      'Not assigned'}
                  </span>

                </div>

              </div>
            )}

          </div>


          {/* =========================
              ATTACHMENTS
             ========================= */}

          <div className="ticket-attachment">

            <h3>
              Attachment
            </h3>

            {ticket.attachment_path ? (
              <>
                <p>
                  Existing attachment:{' '}
                  {ticket.attachment_path}
                </p>

                <button
                  type="button"
                  className="refresh-button"
                  onClick={handleDownload}
                >
                  Download attachment
                </button>
              </>
            ) : (
              <p>
                No attachment uploaded.
              </p>
            )}


            <div className="attachment-upload">

              <input
                type="file"
                onChange={handleFileChange}
                disabled={uploading}
              />

              <button
                type="button"
                className="refresh-button"
                onClick={handleUpload}
                disabled={
                  !selectedFile || uploading
                }
              >
                {uploading
                  ? 'Uploading...'
                  : 'Upload attachment'}
              </button>

            </div>


            {selectedFile && (
              <p>
                Selected file:{' '}
                {selectedFile.name}
              </p>
            )}


            {uploadMessage && (
              <div className="success-message">
                {uploadMessage}
              </div>
            )}

          </div>

        </div>

      </div>

    </section>
  )
}

export default TicketDetail

