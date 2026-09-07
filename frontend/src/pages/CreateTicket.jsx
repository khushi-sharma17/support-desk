import { useState } from 'react'

const API_BASE_URL = 'http://localhost/support-desk/backend/web/v1'

function CreateTicket({ token, onBack, onCreated }) {
  const [clientId, setClientId] = useState('1')
  const [subject, setSubject] = useState('')
  const [description, setDescription] = useState('')
  const [status, setStatus] = useState('open')
  const [priority, setPriority] = useState('medium')
  const [category, setCategory] = useState('general')

  const [creating, setCreating] = useState(false)
  const [error, setError] = useState('')

  const handleSubmit = async (event) => {
    event.preventDefault()

    setError('')
    setCreating(true)

    try {
      const response = await fetch(
        `${API_BASE_URL}/ticket`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({
            client_id: Number(clientId),
            subject,
            description,
            status,
            priority,
            category,
          }),
        }
      )

      const data = await response.json()

      console.log('Create ticket response:', response.status, data)

      if (!response.ok) {
        throw new Error(
          data.message ||
          data.name ||
          `Unable to create ticket. HTTP ${response.status}`
        )
      }

      if (onCreated) {
        onCreated(data)
      }

    } catch (err) {
      setError(
        err.message || 'Unable to create ticket.'
      )
    } finally {
      setCreating(false)
    }
  }

  return (
    <section className="content">

      <div className="welcome-row">

        <div>

          <div className="section-label">
            WORKSPACE
          </div>

          <h1>
            Create Ticket
          </h1>

          <p>
            Create a new customer support ticket.
          </p>

        </div>

        <button
          type="button"
          className="refresh-button"
          onClick={onBack}
        >
          ← Back to tickets
        </button>

      </div>


      <div className="tickets-card">

        {error && (
          <div className="dashboard-error">
            {error}
          </div>
        )}


        <form onSubmit={handleSubmit}>

          <div className="form-group">

            <label htmlFor="clientId">
              Client ID
            </label>

            <input
              id="clientId"
              type="number"
              value={clientId}
              onChange={(event) =>
                setClientId(event.target.value)
              }
              min="1"
              required
              disabled={creating}
            />

          </div>


          <div className="form-group">

            <label htmlFor="ticketSubject">
              Subject
            </label>

            <input
              id="ticketSubject"
              type="text"
              value={subject}
              onChange={(event) =>
                setSubject(event.target.value)
              }
              placeholder="Enter ticket subject"
              required
              disabled={creating}
            />

          </div>


          <div className="form-group">

            <label htmlFor="ticketDescription">
              Description
            </label>

            <textarea
              id="ticketDescription"
              value={description}
              onChange={(event) =>
                setDescription(event.target.value)
              }
              placeholder="Describe the customer's issue"
              rows="6"
              required
              disabled={creating}
            />

          </div>


          <div className="form-group">

            <label htmlFor="ticketStatus">
              Status
            </label>

            <select
              id="ticketStatus"
              value={status}
              onChange={(event) =>
                setStatus(event.target.value)
              }
              disabled={creating}
            >
              <option value="open">Open</option>
              <option value="pending">Pending</option>
              <option value="resolved">Resolved</option>
              <option value="closed">Closed</option>
            </select>

          </div>


          <div className="form-group">

            <label htmlFor="ticketPriority">
              Priority
            </label>

            <select
              id="ticketPriority"
              value={priority}
              onChange={(event) =>
                setPriority(event.target.value)
              }
              disabled={creating}
            >
              <option value="low">
                Low
              </option>

              <option value="medium">
                Medium
              </option>

              <option value="high">
                High
              </option>

              <option value="critical">
                Critical
              </option>
            </select>

          </div>


          <div className="form-group">

            <label htmlFor="ticketCategory">
              Category
            </label>

            <select
              id="ticketCategory"
              value={category}
              onChange={(event) =>
                setCategory(event.target.value)
              }
              disabled={creating}
            >
              <option value="general">
                General
              </option>

              <option value="technical">
                Technical
              </option>

              <option value="billing">
                Billing
              </option>

              <option value="account">
                Account
              </option>
            </select>

          </div>


          <div>

            <button
              type="submit"
              className="login-button"
              disabled={creating}
            >
              {creating
                ? 'Creating...'
                : 'Create Ticket'}
            </button>

          </div>

        </form>

      </div>

    </section>
  )
}

export default CreateTicket