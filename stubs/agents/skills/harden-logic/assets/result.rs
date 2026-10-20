//! Immutable Railway Oriented Programming (ROP) Result pattern in Rust.
//!
//! Provides lightweight pipeline composition and chaining for stateful workflows.

#[derive(Debug, Clone, PartialEq, Eq)]
pub enum PipelineResult<T, E> {
    Success(T),
    Failure(E),
}

impl<T, E> PipelineResult<T, E> {
    pub fn success(data: T) -> Self {
        PipelineResult::Success(data)
    }

    pub fn failure(error: E) -> Self {
        PipelineResult::Failure(error)
    }

    pub fn is_success(&self) -> bool {
        matches!(self, PipelineResult::Success(_))
    }

    pub fn is_failure(&self) -> bool {
        matches!(self, PipelineResult::Failure(_))
    }

    pub fn unwrap(self) -> T
    where
        E: std::fmt::Display,
    {
        match self {
            PipelineResult::Success(val) => val,
            PipelineResult::Failure(err) => panic!("Cannot unwrap failure Result: {}", err),
        }
    }

    pub fn unwrap_error(self) -> Option<E> {
        match self {
            PipelineResult::Success(_) => None,
            PipelineResult::Failure(err) => Some(err),
        }
    }

    /// Pipe the success value into the next step.
    /// Short-circuits on failure and returns the original failure unchanged.
    pub fn then<U, F>(self, next: F) -> PipelineResult<U, E>
    where
        F: FnOnce(T) -> PipelineResult<U, E>,
    {
        match self {
            PipelineResult::Success(val) => next(val),
            PipelineResult::Failure(err) => PipelineResult::Failure(err),
        }
    }
}
